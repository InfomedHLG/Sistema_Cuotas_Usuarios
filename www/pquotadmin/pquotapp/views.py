from django.http import HttpResponse
from django.shortcuts import render, get_object_or_404, redirect
from django.views.decorators.http import require_POST, require_http_methods
from django.contrib.auth.decorators import login_required, user_passes_test
from django.db.models import Sum, Q, F, Avg, BigIntegerField
from django.db.models.functions import Coalesce
from .models import Quota, State
from pquotapp.forms import AddCuota
from django import template
from django.template.defaultfilters import register
from django.core.paginator import Paginator, EmptyPage, PageNotAnInteger
from datetime import datetime
from django.contrib.auth import update_session_auth_hash
from django.contrib import messages
from django.contrib.auth.models import User
from django.utils import timezone
from django.http import JsonResponse
from django.middleware.csrf import get_token



@register.filter
def percentage(value, max_value):
    try:
        return min(round((float(value) / float(max_value)) * 100), 100)
    except (ValueError, ZeroDivisionError):
        return 0

@register.filter
def multiply(value, arg):
    try:
        return float(value) * float(arg)
    except (ValueError, TypeError):
        return 0

@register.filter
def format_bytes(bytes):
    """
    Formatea bytes a la unidad más apropiada
    """
    try:
        bytes = float(bytes)
        if bytes < 1024:
            return f"{bytes:.2f} B"
        elif bytes < 1024 ** 2:
            return f"{bytes/1024:.2f} KB"
        elif bytes < 1024 ** 3:
            return f"{bytes/(1024**2):.2f} MB"
        elif bytes < 1024 ** 4:
            return f"{bytes/(1024**3):.2f} GB"
        else:
            return f"{bytes/(1024**4):.2f} TB"
    except (TypeError, ValueError):
        return "0 B"

@register.filter
def split(value, arg):
    """
    Divide una cadena en una lista usando el separador especificado
    """
    return value.split(arg)

@login_required 
def index(request):
    search_query = request.GET.get('search', '')
    order_by = request.GET.get('order_by', 'last_update')
    order_direction = request.GET.get('direction', 'desc')
    items_per_page = request.GET.get('per_page', request.COOKIES.get('items_per_page', '10'))
    
    try:
        items_per_page = int(items_per_page)
    except ValueError:
        items_per_page = 10

    queryset = Quota.objects.all()
    
    if search_query:
        queryset = queryset.filter(
            Q(client_ip__icontains=search_query) |
            Q(organization__icontains=search_query)
        )
    
    if order_by:
        if order_by == 'used_quota_24h':
            if order_direction == 'desc':
                queryset = queryset.order_by('-used_quota_24h')
            else:
                queryset = queryset.order_by('used_quota_24h')
        else:
            if order_direction == 'asc':
                order_by = order_by.replace('-', '')
            elif order_direction == 'desc':
                order_by = f"-{order_by.replace('-', '')}"
            queryset = queryset.order_by(order_by)

    paginator = Paginator(queryset, items_per_page)
    page = request.GET.get('page')
    try:
        listados = paginator.page(page)
    except PageNotAnInteger:
        listados = paginator.page(1)
    except EmptyPage:
        listados = paginator.page(paginator.num_pages)

    for listado in listados:
        listado.last_update_str = listado.last_update.strftime('%d de %b del %Y %I:%M %p')

    # Cálculos con BigIntegerField para mantener consistencia
    aggregations = Quota.objects.aggregate(
        total_quota=Coalesce(Sum('quota', output_field=BigIntegerField()), 0),
        total_used=Coalesce(Sum('used', output_field=BigIntegerField()), 0),
        total_24h=Coalesce(Sum('used_quota_24h', output_field=BigIntegerField()), 0)
    )

    # Extraer valores
    total_quota = aggregations['total_quota']
    total_used = aggregations['total_used']
    total_24h = aggregations['total_24h']

    # Calcular promedio (como entero)
    total_clients = Quota.objects.count()
    avg_used = total_used // total_clients if total_clients > 0 else 0

    # Calcular porcentaje
    usage_percent = round((total_used * 100) / total_quota, 1) if total_quota > 0 else 0
    
    current_year = datetime.now().year
    
    data = {
        "listados": listados,
        'total': total_clients,
        'pasadocuota': Quota.objects.filter(Q(used__gte=F('quota')),Q(quota__gt=0)).count(),
        'sincuota': Quota.objects.filter(quota=0).count(),
        'total_quota': total_quota,
        'total_used': total_used,
        'avg_used': avg_used,
        'total_24h': total_24h,
        'usage_percent': usage_percent,
        "form": AddCuota(),
        "search_query": search_query,
        "current_order": order_by,
        "current_direction": order_direction,
        'current_year': current_year,
        "items_per_page": items_per_page,
    }  
    response = render(request, 'home.html', data)
    response.set_cookie('items_per_page', items_per_page)
    return response


    
@login_required
def search_institucion(request):
    search_query = request.GET.get('search', '')
    order_by = request.GET.get('order_by', 'last_update')
    order_direction = request.GET.get('direction', 'desc')
    items_per_page = request.GET.get('per_page', '10')
    
    try:
        items_per_page = int(items_per_page)
    except ValueError:
        items_per_page = 10

    queryset = Quota.objects.all()
    
    # Simplificamos la búsqueda para que solo busque en client_ip
    if search_query:
        queryset = queryset.filter(
            client_ip__icontains=search_query
        )
    
    if order_by:
        if order_by == 'used_quota_24h':
            if order_direction == 'desc':
                queryset = queryset.order_by('-used_quota_24h')
            else:
                queryset = queryset.order_by('used_quota_24h')
        else:
            if order_direction == 'asc':
                order_by = order_by.replace('-', '')
            elif order_direction == 'desc':
                order_by = f"-{order_by.replace('-', '')}"
            queryset = queryset.order_by(order_by)

    paginator = Paginator(queryset, items_per_page)
    page = request.GET.get('page')
    try:
        listados = paginator.page(page)
    except PageNotAnInteger:
        listados = paginator.page(1)
    except EmptyPage:
        listados = paginator.page(paginator.num_pages)

    for item in listados:
        item.last_update_str = item.last_update.strftime('%d de %b del %Y %I:%M %p')

    data = {
        "listados": listados,
        "current_order": order_by,
        "current_direction": order_direction,
        "search_query": search_query,
        "items_per_page": items_per_page,
    }
    
    return render(request, 'partials/listado_partial.html', data)









@require_POST
@login_required
def add_institucion(request):
    try:
        form = AddCuota(request.POST)
        if form.is_valid():
            # Convertir MB a bytes si es necesario
            def mb_to_bytes(mb_value):
                try:
                    mb_value = float(mb_value)
                    return int(mb_value * 1048576)
                except (ValueError, TypeError):
                    return 0

            instance = form.save(commit=False)
            instance.quota = mb_to_bytes(form.cleaned_data['quota'])
            instance.used = 0
            instance.organization = instance.client_ip  # Asegurar que organization sea igual a client_ip
            instance.last_update = timezone.now()  # Agregar la fecha actual
            instance.save()

            queryset = Quota.objects.all().order_by('-last_update')
            paginator = Paginator(queryset, 10)
            listados = paginator.page(1)
            
            return render(request, 'partials/listado_partial.html', {'listados': listados})
        else:
            print(f"Errores del formulario: {form.errors}")
            return HttpResponse(status=400)
    except Exception as e:
        print(f"Error al agregar usuario: {str(e)}")
        return HttpResponse(status=400)




@require_POST
@login_required
def reset_institucion(request, pk):
    try:
        page = request.GET.get('page', 1)

        # Actualizar Quota usando update()
        Quota.objects.filter(pk=pk).update(used=0)
        
        try:
            estado = State.objects.get(pk=pk)
            estado.delete()
        except State.DoesNotExist:
            pass
        
        # Obtener y paginar los registros
        queryset = Quota.objects.all().order_by('-last_update')
        paginator = Paginator(queryset, 10)
        try:
            listados = paginator.page(page)
        except PageNotAnInteger:
            listados = paginator.page(1)
        except EmptyPage:
            listados = paginator.page(paginator.num_pages)

        # Formatear last_update para cada registro
        for item in listados:
            item.last_update_str = item.last_update.strftime('%d de %b del %Y %I:%M %p')

        return render(request, 'partials/listado_partial.html', {'listados': listados})
    except Exception as e:
        print(f"Error en reset_institucion: {str(e)}")
        return HttpResponse(status=400)



    

@require_POST
@login_required
def delete_cuota(request, pk):
    try:
        listado = get_object_or_404(Quota, pk=pk)
        listado.quota = 0
        listado.save()
        
        # Obtener todos los registros actualizados
        listados = Quota.objects.all().order_by('-last_update')
        return render(request, 'partials/listado_partial.html', {'listados': listados})
    
    except Exception as e:
        print(f"Error al eliminar cuota: {str(e)}")
        return HttpResponse(status=400)


        

@require_POST
@login_required
def delete_cuota_all(request):
    listado = Quota.objects.all().update(used=0)
    data = {'listado': listado}
    return render(request, 'home.html', data)

@require_http_methods(['DELETE'])
@login_required
def delete_listado(request, pk):
    try:
        listado = get_object_or_404(Quota, pk=pk)
        listado.delete()
        
        # Obtener la lista actualizada
        listados = Quota.objects.all().order_by('-last_update')
        return render(request, 'partials/listado_partial.html', {'listados': listados})
    except Exception as e:
        print(f"Error al eliminar: {str(e)}")
        return HttpResponse(status=400)

 
@register.filter
def intdiv(value, arg):
    """Divide el valor por el argumento y devuelve un entero"""
    try:
        return int(int(value) / int(arg))
    except (ValueError, TypeError, ZeroDivisionError):
        return 0



@require_http_methods(["POST"])
@login_required
def edit_cuota(request):
    try:
        client_ip = request.POST.get('client_ip')
        quota = request.POST.get('quota', '0')
        page = request.GET.get('page', 1)

        def mb_to_bytes(mb_value):
            try:
                mb_value = float(mb_value)
                return int(mb_value * 1048576)
            except (ValueError, TypeError):
                return 0

        quota_bytes = mb_to_bytes(quota)

        # Usar update() en lugar de get_object_or_404 y save()
        Quota.objects.filter(client_ip=client_ip).update(quota=quota_bytes)

        # Obtener y paginar los registros
        queryset = Quota.objects.all().order_by('-last_update')
        paginator = Paginator(queryset, 10)
        try:
            listados = paginator.page(page)
        except PageNotAnInteger:
            listados = paginator.page(1)
        except EmptyPage:
            listados = paginator.page(paginator.num_pages)
        
        # Formatear last_update para cada registro
        for item in listados:
            item.last_update_str = item.last_update.strftime('%d de %b del %Y %I:%M %p')
        
        return render(request, 'partials/listado_partial.html', {'listados': listados})
    
    except Exception as e:
        print(f"Error al editar: {str(e)}")
        return HttpResponse(status=400)



@require_http_methods(["POST"])
@login_required
def edit_client_ip(request):
    try:
        client_ip = request.POST.get('client_ip')
        new_ip = request.POST.get('new_ip')
        page = request.GET.get('page', 1)

        # Obtener el registro existente
        listado = get_object_or_404(Quota, client_ip=client_ip)
        
        # Crear nuevo registro manteniendo TODOS los valores existentes
        Quota.objects.create(
            client_ip=new_ip,
            organization=new_ip,  # Actualizamos también organization para mantener la consistencia
            quota=listado.quota,
            used=listado.used,
            last_update=listado.last_update,
            cache_peer=listado.cache_peer,
            used_quota_24h=listado.used_quota_24h,
            used_quota_mensual=listado.used_quota_mensual,  # Agregamos este campo
            used_quota_anual=listado.used_quota_anual      # Agregamos este campo            
        )
        
        # Eliminar el registro anterior
        listado.delete()

        # Obtener y paginar los registros
        queryset = Quota.objects.all().order_by('-last_update')
        paginator = Paginator(queryset, 10)
        try:
            listados = paginator.page(page)
        except PageNotAnInteger:
            listados = paginator.page(1)
        except EmptyPage:
            listados = paginator.page(paginator.num_pages)

        for item in listados:
            item.last_update_str = item.last_update.strftime('%d de %b del %Y %I:%M %p')           

        return render(request, 'partials/listado_partial.html', {'listados': listados})
    
    except Exception as e:
        print(f"Error al editar usuario: {str(e)}")
        return HttpResponse(status=400)




@login_required
def profile(request):
    return render(request, 'profile.html', {
        'user': request.user,
        'current_year': datetime.now().year,
    })

@login_required
def change_password(request):
    if request.method == 'POST':
        old_password = request.POST.get('old_password')
        new_password = request.POST.get('new_password')
        confirm_password = request.POST.get('confirm_password')
        
        user = request.user
        
        # Verificar contraseña actual
        if not user.check_password(old_password):
            messages.error(request, 'La contraseña actual es incorrecta.')
            return redirect('profile')
            
        # Verificar que las nuevas contraseñas coincidan
        if new_password != confirm_password:
            messages.error(request, 'Las nuevas contraseñas no coinciden.')
            return redirect('profile')
            
        # Cambiar la contraseña
        user.set_password(new_password)
        user.save()
        
        # Actualizar la sesión para evitar desconexión
        update_session_auth_hash(request, user)
        
        messages.success(request, 'Contraseña actualizada correctamente.')
        return redirect('profile')
        
    return redirect('profile')

@login_required
def update_profile(request):
    if request.method == 'POST':
        first_name = request.POST.get('first_name')
        last_name = request.POST.get('last_name')
        email = request.POST.get('email')
        
        user = request.user
        if first_name and last_name:
            user.first_name = first_name
            user.last_name = last_name
        if email:
            user.email = email
        
        user.save()
        messages.success(request, 'Perfil actualizado correctamente.')
        
    return redirect('profile')

@user_passes_test(lambda u: u.is_superuser)
def create_user(request):
    if request.method == 'POST':
        username = request.POST.get('username')
        password = request.POST.get('password')
        email = request.POST.get('email')
        first_name = request.POST.get('first_name')
        last_name = request.POST.get('last_name')
        is_staff = request.POST.get('is_staff') == 'on'
        is_superuser = request.POST.get('is_superuser') == 'on'

        try:
            # Verificar si el usuario ya existe
            if User.objects.filter(username=username).exists():
                messages.error(request, 'El nombre de usuario ya existe.')
                return redirect('profile')

            # Crear el nuevo usuario
            user = User.objects.create_user(
                username=username,
                email=email,
                password=password,
                first_name=first_name,
                last_name=last_name
            )
            
            user.is_staff = is_staff
            user.is_superuser = is_superuser
            user.save()
            
            messages.success(request, f'Usuario {username} creado exitosamente.')
        except Exception as e:
            messages.error(request, f'Error al crear el usuario: {str(e)}')
            
    return redirect('profile')

@login_required
def auto_update(request):
    order_by = request.GET.get('order_by', 'last_update')
    order_direction = request.GET.get('direction', 'desc')
    items_per_page = request.GET.get('per_page', '10')
    
    try:
        items_per_page = int(items_per_page)
    except ValueError:
        items_per_page = 10
        
    queryset = Quota.objects.all()
    
    # Aplicar ordenamiento
    if order_by:
        if order_direction == 'desc':
            queryset = queryset.order_by(f'-{order_by}')
        else:
            queryset = queryset.order_by(order_by)
            
    # Recuperamos los parámetros actuales
    page = request.GET.get('page', '1')
    search_query = request.GET.get('search', '')

    paginator = Paginator(queryset, items_per_page)
    try:
        listados = paginator.page(page)
    except PageNotAnInteger:
        listados = paginator.page(1)
    except EmptyPage:
        listados = paginator.page(paginator.num_pages)
        
    # Agregar el formato de última actualización para cada listado
    for listado in listados:
        listado.last_update_str = listado.last_update.strftime('%d de %b del %Y %I:%M %p')
 
    data = {
        "listados": listados,
        "current_order": order_by,
        "current_direction": order_direction,
        "search_query": search_query,
        "items_per_page": items_per_page,
        "include_modals": True,
    }
    
    return render(request, 'partials/listado_partial.html', data)

@login_required
def update_stats(request):
    # Calcular la suma total de 'used' y 'quota'
    stats = Quota.objects.aggregate(
        total_used=Coalesce(Sum('used', output_field=BigIntegerField()), 0),
        total_quota=Coalesce(Sum('quota', output_field=BigIntegerField()), 0)
    )
    
    return render(request, 'partials/stats_update.html', {
        'total_used': stats['total_used'],
        'total_quota': stats['total_quota']
    })

@login_required
def update_modals(request):
    # Obtener los mismos datos que se usan en la tabla
    queryset = Quota.objects.all()
    search_query = request.GET.get('search', '')
    
    if search_query:
        queryset = queryset.filter(
            Q(client_ip__icontains=search_query) |
            Q(organization__icontains=search_query)
        )
    
    return render(request, 'partials/modals.html', {
        'listados': queryset
    })


# Agregar esta importación al inicio del archivo junto con las otras importaciones


# ... resto de las importaciones y código existente ...

@require_http_methods(["GET"])
def check_session(request):
    if request.user.is_authenticated:
        # Renovar la sesión
        request.session.modified = True
        # Obtener un nuevo token CSRF
        csrf_token = get_token(request)
        return JsonResponse({
            'status': 'active',
            'csrf_token': csrf_token
        })
    return JsonResponse({'status': 'expired'}, status=440)
