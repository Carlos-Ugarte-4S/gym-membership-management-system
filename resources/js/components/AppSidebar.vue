<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, CreditCard, Settings, Clock, Tag, FileText } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage<any>();
const userRole = computed(() => page.props.auth.user?.rol);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
    ];

    if (userRole.value === 'Recepcionista' || userRole.value === 'Administrador') {
        items.push(
            {
                title: 'Clientes',
                href: '/clientes',
                icon: Users,
            },
            {
                title: 'Registrar Membresía',
                href: '/membresias',
                icon: FileText,
            },
             {
                title: 'Reportes',
                href: '/reportes',
                icon: FileText,
            }
        );
    }

    if (userRole.value === 'Administrador') {
        items.push(
            {
                title: 'Gestión Usuarios',
                href: '/admin/usuarios',
                icon: Settings,
            },
            {
                title: 'Tipos Membresía',
                href: '/admin/tipos-membresia',
                icon: CreditCard,
            },
            {
                title: 'Turnos',
                href: '/admin/turnos',
                icon: Clock,
            },
            {
                title: 'Promociones',
                href: '/admin/promociones',
                icon: Tag,
            },
            {
                title: 'Backup',
                href: '/admin/backup',
                icon: Folder,
            }
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
