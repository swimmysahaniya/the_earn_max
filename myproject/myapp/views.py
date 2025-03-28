from django.shortcuts import render
from django.http import HttpResponse

from django.http import JsonResponse
from django.views import View

from rest_framework import generics, status
from .models import Task, TaskVideo, Payment, CompletedTask, Users, Refund, MonthlyIncome, Wallet, Withdrawal
from .serializers import TaskSerializer, TaskVideoSerializer, PaymentSerializer, CompletedTaskSerializer, \
        UsersSerializer, SignupSerializer, RefundSerializer, MonthlyIncomeSerializer, \
        WalletSerializer, WithdrawalSerializer

from rest_framework.response import Response
from rest_framework.permissions import AllowAny
from rest_framework.views import APIView
import json

from django.views.decorators.csrf import csrf_exempt
from django.utils.decorators import method_decorator
from rest_framework.decorators import api_view


class TaskListAPI(generics.ListAPIView):
    queryset = Task.objects.all()
    serializer_class = TaskSerializer


class TaskVideoListAPI(generics.ListAPIView):
    queryset = TaskVideo.objects.all()
    serializer_class = TaskVideoSerializer


# List all payments or create a new one
class PaymentListCreateView(generics.ListCreateAPIView):
    queryset = Payment.objects.all()
    serializer_class = PaymentSerializer


# Retrieve, update, or delete a specific payment
class PaymentDetailView(View):
    def get(self, request, id):
        try:
            membership = Payment.objects.filter(id=id, status="1").values().first()
            if not membership:
                return JsonResponse({"error": "Membership not found"}, status=404)

            return JsonResponse(membership, safe=False)
        except Exception as e:
            return JsonResponse({"error": str(e)}, status=500)


def home(request):
    return HttpResponse("Hello, Django!")


# List and Create View
class CompletedTaskListCreateView(generics.ListCreateAPIView):
    queryset = CompletedTask.objects.all()
    serializer_class = CompletedTaskSerializer


# Retrieve, Update, and Delete View
class CompletedTaskDetailView(generics.RetrieveUpdateDestroyAPIView):
    queryset = CompletedTask.objects.all()
    serializer_class = CompletedTaskSerializer


# API to list users and create a new one
class UserListCreateAPIView(generics.ListCreateAPIView):
    queryset = Users.objects.all()
    serializer_class = UsersSerializer


class SignupAPIView(APIView):
    permission_classes = [AllowAny]

    def get(self, request):
        return Response({"message": "Use POST to create a new user"}, status=status.HTTP_200_OK)

    def post(self, request):
        serializer = SignupSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        user = serializer.save()
        return Response({"message": "Signup successful!", "user_id": user.id}, status=status.HTTP_201_CREATED)


class RefundListAPIView(generics.ListAPIView):
    queryset = Refund.objects.all()
    serializer_class = RefundSerializer


@method_decorator(csrf_exempt, name='dispatch')
class MonthlyIncomeAPI(View):

    def get(self, request, user_mobile=None):
        if user_mobile:
            try:
                user = Users.objects.get(mobile=user_mobile)
                incomes = MonthlyIncome.objects.filter(user=user)
                serializer = MonthlyIncomeSerializer(incomes, many=True)
                return JsonResponse(serializer.data, safe=False)
            except Users.DoesNotExist:
                return JsonResponse({"error": "User not found"}, status=404)
        else:
            incomes = MonthlyIncome.objects.all()
            serializer = MonthlyIncomeSerializer(incomes, many=True)
            return JsonResponse(serializer.data, safe=False)

    def post(self, request):
        try:
            data = json.loads(request.body)
            user = Users.objects.get(mobile=data.get('user_mobile'))

            income = MonthlyIncome.objects.create(
                user_mobile_id=user,
                total_referred_investment=data.get('total_referred_investment', 0.00),
                monthly_income=data.get('monthly_income', 0.00),
                status=data.get('status', False),
            )
            serializer = MonthlyIncomeSerializer(income)
            return JsonResponse(serializer.data, status=201)

        except Users.DoesNotExist:
            return JsonResponse({"error": "User not found"}, status=404)

        except Exception as e:
            return JsonResponse({"error": str(e)}, status=400)


@api_view(["GET"])
def get_wallet(request, user_mobile):
    """Fetch wallet details for a given user mobile"""
    try:
        wallet = Wallet.objects.get(user__username=user_mobile)  # Assuming `username` stores the mobile
        serializer = WalletSerializer(wallet)
        return Response(serializer.data)
    except Wallet.DoesNotExist:
        return Response({"error": "Wallet not found"}, status=404)


@api_view(["GET"])
def get_withdrawals(request, user_mobile):
    """Fetch all withdrawals for a given user mobile"""
    try:
        user = Users.objects.get(mobile=user_mobile)  # Assuming `username` stores the mobile number
        withdrawals = Withdrawal.objects.filter(user_mobile_id=user).order_by("-created_at")
        serializer = WithdrawalSerializer(withdrawals, many=True)
        return Response(serializer.data)
    except Users.DoesNotExist:
        return Response({"error": "User not found"}, status=404)

