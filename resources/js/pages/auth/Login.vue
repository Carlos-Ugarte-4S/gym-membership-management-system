<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

// Formulario reactivo adaptado a tu base de datos (CU-01)
const form = useForm({
    login: '', // Cambiado de username a login para coincidir con AuthController
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login.post'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Sistema de Membresías" description="Ingresa tus credenciales para acceder al panel de control">
        <Head title="Iniciar Sesión" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="login">Nombre de Usuario</Label>
                    <Input
                        id="login"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        v-model="form.login"
                        placeholder="Ej. admin"
                    />
                    <InputError :message="form.errors.login" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Contraseña</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between" tabindex="3">
                    <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                        <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                        <span class="text-sm text-muted-foreground">Recordar mi sesión</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-extrabold shadow-md hover:shadow-lg transition-all duration-200" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                    Iniciar Sesión
                </Button>
            </div>
        </form>
    </AuthBase>
</template>