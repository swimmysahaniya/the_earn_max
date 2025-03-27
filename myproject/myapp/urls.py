from django.urls import path
from . import views
from .views import TaskListAPI, TaskVideoListAPI, PaymentListCreateView, PaymentDetailView, CompletedTaskListCreateView, \
    CompletedTaskDetailView, ReferralListCreateAPIView, UserListCreateAPIView, SignupAPIView, RefundListAPIView, \
    MonthlyIncomeAPI, get_wallet, get_withdrawals


urlpatterns = [
    path('', views.home, name='home'),
    path('api/tasks/', TaskListAPI.as_view(), name='task-list'),
    path('api/videos/', TaskVideoListAPI.as_view(), name='video-list'),
    path('api/payments/', PaymentListCreateView.as_view(), name='payment-list-create'),
    path('api/payments/<int:id>/', PaymentDetailView.as_view(), name='payment-detail'),
    path('api/completed-tasks/', CompletedTaskListCreateView.as_view(), name='completed-task-list-create'),
    path('api/completed-tasks/<int:pk>/', CompletedTaskDetailView.as_view(), name='completed-task-detail'),

    path('api/referrals/', ReferralListCreateAPIView.as_view(), name='referrals-api'),
    path('api/users/', UserListCreateAPIView.as_view(), name='users-api'),
    path('api/signup/', SignupAPIView.as_view(), name='signup-api'),

    path('api/refunds/', RefundListAPIView.as_view(), name='refund-list'),
    path('api/monthly-income/', MonthlyIncomeAPI.as_view(), name='monthly_income_list'),
    path('api/monthly-income/<str:user_mobile>/', MonthlyIncomeAPI.as_view(), name='monthly_income_detail'),

    path("wallet/<str:user_mobile>/", get_wallet, name="get_wallet"),
    path("withdrawals/<str:user_mobile>/", get_withdrawals, name="get_withdrawals"),
]

