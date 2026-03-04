from django.urls import path
from . import views

urlpatterns = [
    path('', views.index, name='index'),
    path('generate/', views.generate_qr, name='generate_qr'),
    path('decode/', views.decode_qr, name='decode_qr'),
]