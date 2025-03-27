from rest_framework import serializers
from .models import Task, TaskVideo, Payment, CompletedTask, Referral, Users, Refund, MonthlyIncome, Wallet, Withdrawal
from django.contrib.auth.hashers import make_password


class TaskVideoSerializer(serializers.ModelSerializer):
    video_url = serializers.SerializerMethodField()

    class Meta:
        model = TaskVideo
        fields = ['id', 'task', 'video_url', 'uploaded_at']

    def get_video_url(self, obj):
        request = self.context.get('request')
        return request.build_absolute_uri(obj.video.url)


class TaskSerializer(serializers.ModelSerializer):
    videos = TaskVideoSerializer(many=True, read_only=True)

    class Meta:
        model = Task
        fields = ['id', 'task_number', 'title', 'amount', 'earning', 'no_of_videos', 'description', 'videos']


class PaymentSerializer(serializers.ModelSerializer):
    class Meta:
        model = Payment
        fields = '__all__'  # Include all fields


class CompletedTaskSerializer(serializers.ModelSerializer):
    class Meta:
        model = CompletedTask
        fields = '__all__'  # Includes all fields


class ReferralSerializer(serializers.ModelSerializer):
    class Meta:
        model = Referral
        fields = '__all__'


class UsersSerializer(serializers.ModelSerializer):
    class Meta:
        model = Users
        fields = '__all__'


class SignupSerializer(serializers.ModelSerializer):
    confirm_password = serializers.CharField(write_only=True, required=True)

    class Meta:
        model = Users
        fields = ["mobile", "password", "confirm_password", "invited_by"]

    def validate(self, data):
        # Check if passwords match
        if data["password"] != data["confirm_password"]:
            raise serializers.ValidationError({"password": "Passwords do not match!"})

        # Check if the referral code exists and belongs to an approved user
        invited_by = data.get("invited_by")
        if invited_by and not Users.objects.filter(referral_code=invited_by, status="1").exists():
            raise serializers.ValidationError({"invited_by": "Invalid referral code!"})

        return data

    def create(self, validated_data):
        validated_data.pop("confirm_password")  # Remove confirm_password before saving

        # Generate a unique referral code
        import random, string
        referral_code = "".join(random.choices(string.ascii_uppercase + string.digits, k=8))

        # Hash the password
        validated_data["password"] = make_password(validated_data["password"])
        validated_data["referral_code"] = referral_code

        user = Users.objects.create(**validated_data)

        # If a valid referrer exists, create a referral record
        if validated_data.get("invited_by"):
            referrer = Users.objects.get(referral_code=validated_data["invited_by"])
            Referral.objects.create(
                referrer_mobile=referrer.mobile,
                referred_mobile=user.mobile,
                invested_amount=0  # Default 0, updated when user invests
            )

        return user


class RefundSerializer(serializers.ModelSerializer):
    class Meta:
        model = Refund
        fields = '__all__'


class MonthlyIncomeSerializer(serializers.ModelSerializer):
    user_mobile = serializers.CharField(source="user.mobile", read_only=True)  # Show mobile instead of user ID

    class Meta:
        model = MonthlyIncome
        fields = ['id', 'user_mobile', 'total_referred_investment', 'monthly_income', 'status', 'created_at']


class WalletSerializer(serializers.ModelSerializer):
    class Meta:
        model = Wallet
        fields = "__all__"


class WithdrawalSerializer(serializers.ModelSerializer):
    class Meta:
        model = Withdrawal
        fields = "__all__"



