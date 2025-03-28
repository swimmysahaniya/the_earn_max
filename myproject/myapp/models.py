from django.db import models
from django.core.exceptions import ValidationError
from django.core.validators import MinValueValidator, MaxValueValidator
from django.utils.text import slugify


# Validator to restrict file formats and size
def validate_video_file(value):
    if not value.name.endswith(('.mp4', '.mov', '.avi', '.mkv')):
        raise ValidationError("Invalid video format. Upload MP4, MOV, AVI, or MKV.")
    if value.size > 50 * 1024 * 1024:  # 50MB limit
        raise ValidationError("File size exceeds 50MB.")


class Task(models.Model):
    task_number = models.CharField(max_length=100, unique=True)
    title = models.CharField(max_length=255)
    amount = models.IntegerField(default=0)
    earning = models.IntegerField(default=0)
    no_of_videos = models.IntegerField(default=0)
    description = models.TextField(blank=True)

    def __str__(self):
        return f"{self.task_number} - {self.title}"


class TaskVideo(models.Model):
    task = models.ForeignKey(Task, on_delete=models.CASCADE, related_name="videos")
    video = models.FileField(upload_to="videos/", validators=[validate_video_file])
    uploaded_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Video for Task {self.task.task_number}"


class Upis(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
    ]

    number = models.CharField(max_length=100, unique=True)
    upi_id = models.CharField(max_length=25)
    description = models.TextField(blank=True)
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')


class CompletedTask(models.Model):
    task = models.ForeignKey(Task, on_delete=models.CASCADE, related_name="completed_tasks")
    user_mobile = models.CharField(max_length=20)
    completed_tasks = models.IntegerField(default=0)
    total_earnings = models.IntegerField(default=0)
    date = models.DateField(auto_now_add=True)

    class Meta:
        unique_together = ('user_mobile', 'date')  # Enforces unique constraint on user_mobile and date

    def __str__(self):
        return f"{self.user_mobile} - {self.completed_tasks} tasks - {self.total_earnings} INR"


class Users(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    REFUND_STATUS_CHOICES = [
        ('0', 'No Refund'),
        ('1', 'Full Refund'),
    ]

    mobile = models.CharField(max_length=15, unique=True, db_collation="utf8mb4_general_ci")
    password = models.CharField(max_length=255)
    referral_code = models.CharField(max_length=10, unique=True)
    invited_by = models.CharField(max_length=10, null=True, blank=True)
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)
    membership_level = models.IntegerField(default=0)
    refund_status = models.CharField(max_length=1, choices=REFUND_STATUS_CHOICES, default='0')  # refund has been given

    def __str__(self):
        return self.mobile


class Refund(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='refunds', default=0)
    refunded_amount = models.DecimalField(max_digits=10, decimal_places=2)
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Refund to {self.user_mobile}"


class MonthlyIncome(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name="monthly_income", null=False)
    total_referred_investment = models.DecimalField(max_digits=10, decimal_places=2, default=0.00)
    monthly_income = models.DecimalField(max_digits=10, decimal_places=2, default=0.00)
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.user_mobile.mobile} - ₹{self.monthly_income}"


class Wallet(models.Model):
    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name="wallet")
    balance = models.DecimalField(max_digits=10, decimal_places=2, default=0.00)
    total_withdrawn = models.DecimalField(max_digits=10, decimal_places=2, default=0.00)
    tds_earning = models.DecimalField(max_digits=10, decimal_places=2, default=0.00)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"Wallet of {self.user_mobile.mobile} - Balance: {self.balance}"


class Withdrawal(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name="withdrawals")
    withdrawal_amount = models.DecimalField(max_digits=10, decimal_places=2)
    tds_amount = models.DecimalField(max_digits=10, decimal_places=2)
    final_amount = models.DecimalField(max_digits=10, decimal_places=2)  # After TDS deduction
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Withdrawal {self.id} - {self.user_mobile.mobile} - {self.withdrawal_amount}"


class ExtraIncome(models.Model):
    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name="extra_income")
    extra_amount = models.DecimalField(max_digits=10, decimal_places=2)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Extra Income {self.id} - {self.user_mobile.mobile} - {self.extra_amount}"


class Payment(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='payments')
    amount = models.IntegerField()
    transaction_code = models.BigIntegerField(
        default=0,
        validators=[MinValueValidator(0), MaxValueValidator(999999999999)]  # 12-digit limit
    )
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.user_mobile} - {self.amount}"


def pan_card_upload_path(instance, filename):
    """Generates file path for the uploaded PAN card image."""
    return f"pan_cards/"


class KYC(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='kyc')
    name = models.CharField(max_length=255)
    address = models.TextField()
    pan_number = models.CharField(max_length=10, unique=True, blank=True, null=True)
    pan_card_image = models.ImageField(upload_to=pan_card_upload_path, blank=True, null=True)
    email_id = models.EmailField(unique=True, blank=True, null=True)
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"KYC for {self.user_mobile} ({self.status})"


class BankDetails(models.Model):
    STATUS_CHOICES = [
        ('0', 'Pending'),
        ('1', 'Approved'),
        ('2', 'Rejected'),
    ]

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='bank_details')
    name = models.CharField(max_length=255)

    # UPI Details
    upi_id = models.CharField(max_length=50, blank=True, null=True)

    # Bank Account Details
    account_number = models.CharField(max_length=20, blank=True, null=True)
    bank_name = models.CharField(max_length=255, blank=True, null=True)
    ifsc_code = models.CharField(max_length=20, blank=True, null=True)
    branch_name = models.CharField(max_length=255, blank=True, null=True)

    mobile_number = models.CharField(max_length=15, default=0)
    status = models.CharField(max_length=1, choices=STATUS_CHOICES, default='0')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.name} ({self.user_mobile})"


def profile_upload_path(instance, filename):
    """Generates file path for the uploaded PAN card image."""
    return f"profile/{instance.user_mobile.mobile}_{filename}"


class Profile(models.Model):

    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='profile')
    name = models.CharField(max_length=255)
    email_id = models.EmailField(unique=True, blank=True, null=True)
    profile_image = models.ImageField(upload_to=profile_upload_path, blank=True, null=True)
    address = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Profile for {self.user_mobile}"


class Blog(models.Model):
    title = models.CharField(max_length=200)
    slug = models.SlugField(unique=True, blank=True)
    content = models.TextField()
    image = models.ImageField(upload_to='blogs')
    author = models.CharField(max_length=100)
    created_at = models.DateTimeField(auto_now_add=True)

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.title)
        super().save(*args, **kwargs)

    def __str__(self):
        return self.title


class Gallery(models.Model):
    title = models.CharField(max_length=255)
    image = models.ImageField(upload_to="gallery")
    uploaded_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.title


class UserActivity(models.Model):
    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='user_activity')
    login_time = models.DateTimeField(null=True, blank=True)
    logout_time = models.DateTimeField(null=True, blank=True)
    session_duration = models.DurationField(null=True, blank=True)

    def __str__(self):
        return f"{self.user_mobile} - {self.login_time} to {self.logout_time}"


class SupportTicket(models.Model):
    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name='support_ticket')
    subject = models.CharField(max_length=255)
    ticket_id = models.IntegerField(default=0)
    message = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)
    status = models.CharField(
        max_length=20, choices=[('open', 'Open'), ('in_progress', 'In Progress'), ('closed', 'Closed')], default='open'
    )

    def __str__(self):
        return f"{self.user_mobile} - {self.subject}"


class WatchedVideo(models.Model):
    user_mobile = models.ForeignKey(Users, on_delete=models.CASCADE, to_field='mobile', related_name="watched_videos")
    task_id = models.CharField(max_length=255)  # Store the task ID
    video_url = models.CharField(max_length=500)  # Store video URL or video ID
    watched_at = models.DateTimeField(auto_now_add=True)  # Timestamp of when the video was watched

    def __str__(self):
        return f"{self.user_mobile} watched {self.video_url}"




