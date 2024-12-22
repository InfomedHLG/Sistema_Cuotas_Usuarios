from django.contrib import admin
from django.urls import path, re_path, include
from pquotapp.views import index, add_institucion, reset_institucion, delete_listado, delete_cuota, delete_cuota_all, search_institucion, edit_cuota, edit_client_ip, profile, change_password, update_profile, create_user, auto_update, update_stats, update_modals, check_session
urlpatterns = [
    path('admin/', admin.site.urls),
    path('accounts/', include('django.contrib.auth.urls')),
    path('', index, name="index"),
    path('add-institucion/', add_institucion, name="add-institucion"),
    re_path(r"^delete-listado/(?P<pk>[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/$", delete_listado, name="delete-listado"),
    re_path(r"^delete-cuota/(?P<pk>[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/$", delete_cuota, name="delete-cuota"),
    path('search-institucion/', search_institucion, name="search-institucion"),
    path('reset-institucion/<str:pk>/', reset_institucion, name="reset-institucion"),
    path('delete-listado/<str:pk>/', delete_listado, name="delete-listado"),
    path('delete-cuota/<str:pk>/', delete_cuota, name="delete-cuota"),
    path('delete-cuota-all/', delete_cuota_all, name="delete-cuota-all"),
    path('edit-institucion/', edit_cuota, name="edit-institucion"),
    path('edit-client-ip/', edit_client_ip, name="edit-client-ip"),
    path('profile/', profile, name='profile'),
    path('change-password/', change_password, name='change-password'),
    path('update-profile/', update_profile, name='update-profile'),
    path('create-user/', create_user, name='create-user'),
    path('auto-update/', auto_update, name='auto-update'),
    path('update-stats/', update_stats, name='update-stats'),
    path('update-modals/', update_modals, name='update-modals'),
    path('check-session/', check_session, name='check-session'),
]

