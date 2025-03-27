from django.contrib import admin
from .models import Task, TaskVideo, Upis, Payment, CompletedTask, Users, Refund, MonthlyIncome, Withdrawal, KYC, \
    BankDetails, Profile, Wallet, ExtraIncome, Blog, Gallery, UserActivity, SupportTicket
from django.db.models import Sum, Count, Avg, DurationField, ExpressionWrapper, F
from django.utils.html import format_html
from django.utils.timezone import now
from datetime import timedelta


class TaskVideoInline(admin.TabularInline):  # Inline for video uploads
    model = TaskVideo
    extra = 1  # Allows adding videos directly in the Task admin panel


@admin.register(Upis)
class UpisAdmin(admin.ModelAdmin):
    list_display = ("id", "upi_id", "colored_status")

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin


@admin.register(Task)
class TaskAdmin(admin.ModelAdmin):
    list_display = ("title", "amount", "earning", "no_of_videos")
    search_fields = ("title", "amount", "earning", "no_of_videos")
    inlines = [TaskVideoInline]  # Attach video upload inline to Task admin


@admin.register(Payment)
class PaymentAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "amount", 'transaction_code', "colored_status", "created_at")
    list_filter = ("status", "created_at")
    search_fields = ('user_mobile__mobile', 'transaction_code')

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin


@admin.register(CompletedTask)
class CompletedTaskAdmin(admin.ModelAdmin):
    list_display = ('user_mobile', 'task', 'completed_tasks', 'total_earnings', 'date')
    list_filter = ('date',)
    search_fields = ('user_mobile__mobile',)


@admin.register(Refund)
class RefundAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "refunded_amount", "colored_status", "created_at")
    list_filter = ("status", "created_at")
    actions = ['approve_refunds']
    search_fields = ('user_mobile__mobile',)

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin


@admin.register(MonthlyIncome)
class MonthlyIncomeAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "total_referred_investment", "monthly_income", "colored_status", "created_at")
    list_filter = ("status", "created_at")
    search_fields = ('user_mobile__mobile',)

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin


@admin.register(Withdrawal)
class WithdrawalAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "withdrawal_amount", "tds_amount", "final_amount", "colored_status", "created_at")
    list_filter = ("status", "created_at")
    search_fields = ('user_mobile__mobile',)

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin


class PaymentInline(admin.TabularInline):
    model = Payment
    extra = 0
    fields = ('amount', 'status', 'created_at')
    readonly_fields = ('amount', 'status', 'created_at')


class UsersAdmin(admin.ModelAdmin):
    list_display = ('mobile', 'referral_chain', 'created_at', 'colored_status', 'colored_refund_status', 'referral_count', 'total_referred_investment')
    search_fields = ('mobile', 'referral_code', 'invited_by')
    list_filter = ("status", "created_at")
    inlines = [PaymentInline]

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin

    def colored_refund_status(self, obj):
        color = "red" if obj.refund_status == "0" else "green"
        return format_html('<span style="color: {};">{}</span>', color, obj.get_refund_status_display())

    colored_refund_status.admin_order_field = 'refund_status'
    colored_refund_status.short_description = 'Refund Status'  # Display name in admin

    def referral_count(self, obj):
        """ Count how many users this user has referred """
        return Users.objects.filter(invited_by=obj.referral_code).count()
    referral_count.short_description = "Direct Referrals"

    def total_referred_investment(self, obj):
        """ Sum total investment from referred users """
        total = Payment.objects.filter(
            user_mobile__in=Users.objects.filter(invited_by=obj.referral_code),
            status='1'
        ).aggregate(Sum('amount'))['amount__sum']
        return total if total else 0
    total_referred_investment.short_description = "Total Referred Investment (₹)"

    def referral_chain(self, obj):
        """ Display multi-level referral chain with earnings from myapp_payment """

        def get_referral_tree(user, level=0):
            referrals = Users.objects.filter(invited_by=user.referral_code)
            if not referrals.exists():
                return ""

            tree_html = "<ul>"
            for ref_user in referrals:
                # Fetch total earnings from myapp_payment (only approved payments)
                referral_earnings = (
                        Payment.objects.filter(user_mobile_id=ref_user.mobile, status='1')
                        .aggregate(total_earned=Sum("amount"))
                        .get("total_earned", 0) or 0
                )

                indent = "&nbsp;&nbsp;&nbsp;" * level  # Indentation for hierarchy
                tree_html += (
                    f"<li>{indent}<strong>{ref_user.mobile}</strong> "
                    f"(Joined: {ref_user.created_at.strftime('%Y-%m-%d')}) - "
                    f"<span style='color: green;'>Investment: ₹{referral_earnings}</span>"
                )
                tree_html += get_referral_tree(ref_user, level + 1)  # Recursive call for sub-referrals
                tree_html += "</li>"
            tree_html += "</ul>"

            return tree_html

        return format_html(get_referral_tree(obj))

    referral_chain.short_description = "Referral Chain with Investment"


(admin.site.register(Users, UsersAdmin))


@admin.register(KYC)
class KYCAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "pan_number", "pan_card_image_display", "colored_status", "created_at")
    list_filter = ("status", "created_at")
    search_fields = ("user_mobile__mobile", "pan_number", "email_id")
    readonly_fields = ("created_at",)

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin

    def pan_card_image_display(self, obj):
        if obj.pan_card_image:
            return format_html('<img src="{}" width="100px" style="border-radius:5px;"/>', obj.pan_card_image.url)
        return "No Image"

    pan_card_image_display.short_description = "PAN Card Image"


@admin.register(BankDetails)
class BankDetailsAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "name", "upi_id", "account_number", "bank_name", "ifsc_code", "branch_name", "colored_status", "created_at")
    search_fields = ("user_mobile__mobile", "name", "upi_id", "account_number", "ifsc_code")
    list_filter = ("status", "created_at")

    def colored_status(self, obj):
        status_colors = {
            "0": "red",  # 0 = Red
            "1": "green",  # 1 = Green
            "2": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin


@admin.register(Profile)
class ProfileAdmin(admin.ModelAdmin):
    list_display = ("user_mobile", "name", "email_id", "address")
    list_filter = ("created_at",)
    search_fields = ("user_mobile__mobile", "name", "email_id")
    readonly_fields = ("created_at",)


@admin.register(Wallet)
class WalletAdmin(admin.ModelAdmin):
    list_display = ('user_mobile', 'balance', 'total_withdrawn', 'tds_earning', 'updated_at')
    search_fields = ('user_mobile__mobile',)
    list_filter = ('updated_at',)


@admin.register(ExtraIncome)
class ExtraIncomeAdmin(admin.ModelAdmin):
    list_display = ('user_mobile', 'extra_amount', 'created_at')
    search_fields = ('user_mobile__mobile',)
    list_filter = ('created_at',)


@admin.register(Blog)
class BlogAdmin(admin.ModelAdmin):
    list_display = ("title", "author", "created_at")
    prepopulated_fields = {"slug": ("title",)}
    search_fields = ('title',)
    list_filter = ('created_at',)


admin.site.register(Gallery)


@admin.register(UserActivity)
class UserActivityAdmin(admin.ModelAdmin):
    list_display = ('user_mobile', 'login_time', 'logout_time', 'session_duration')

    def changelist_view(self, request, extra_context=None):
        # Calculate user activity stats
        today = now().date()

        daily_active_users = UserActivity.objects.filter(login_time__date=today).values('user_mobile').distinct().count()
        weekly_active_users = UserActivity.objects.filter(login_time__week=today.isocalendar()[1]).values('user_mobile').distinct().count()
        monthly_active_users = UserActivity.objects.filter(login_time__month=today.month).values('user_mobile').distinct().count()

        avg_session_duration = UserActivity.objects.aggregate(avg_duration=Avg('session_duration'))['avg_duration']

        returning_users = UserActivity.objects.filter(login_time__date=today).exclude(
            user_mobile__in=UserActivity.objects.filter(login_time__date=today).values_list('user_mobile', flat=True)
        ).values('user_mobile').distinct().count()

        new_users_today = UserActivity.objects.filter(login_time__date=today).count()

        top_users = UserActivity.objects.values('user_mobile').annotate(login_count=Count('id')).order_by('-login_count')[:10]

        extra_context = extra_context or {}
        extra_context['daily_active_users'] = daily_active_users
        extra_context['weekly_active_users'] = weekly_active_users
        extra_context['monthly_active_users'] = monthly_active_users
        extra_context['avg_session_duration'] = avg_session_duration
        extra_context['returning_users'] = returning_users
        extra_context['new_users_today'] = new_users_today
        extra_context['top_users'] = top_users

        return super().changelist_view(request, extra_context=extra_context)


@admin.register(SupportTicket)
class SupportTicketAdmin(admin.ModelAdmin):
    list_display = ('user_mobile', 'ticket_id', 'subject', 'colored_status', 'created_at')
    list_filter = ('status', 'created_at')
    search_fields = ('user_mobile__mobile', 'ticket_id', 'subject')

    def colored_status(self, obj):
        status_colors = {
            "open": "red",  # 0 = Red
            "in_progress": "green",  # 1 = Green
            "closed": "blue"  # 2 = Blue
        }
        color = status_colors.get(obj.status, "black")  # Default to black if unknown
        return format_html('<span style="color: {};">{}</span>', color, obj.get_status_display())

    colored_status.admin_order_field = 'status'
    colored_status.short_description = 'Status'  # Display name in admin
