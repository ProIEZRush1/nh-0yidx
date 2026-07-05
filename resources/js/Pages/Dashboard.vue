<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
});

const page = usePage();

const businessName = computed(() => page.props.name ?? 'Mi Negocio');
const userFirstName = computed(() => {
    const name = (page.props.auth?.user?.name ?? '').trim();
    return name ? name.split(/\s+/)[0] : '';
});

function formatearPrecio(centavos) {
    return '$' + (centavos / 100).toLocaleString('es-MX', { minimumFractionDigits: 0 }) + ' MXN';
}

const cards = computed(() => [
    {
        label: 'Membresías activas',
        value: props.stats.planes,
        hint: 'Planes disponibles en el bot',
        gradient: 'from-[#dc2626] to-[#f97316]',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        href: route('planes.index'),
    },
    {
        label: 'Inscripciones del mes',
        value: props.stats.pedidosMes,
        hint: `${props.stats.pedidosConfirmados} confirmadas en total`,
        gradient: 'from-[#ea580c] to-[#facc15]',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        href: route('pedidos.index'),
    },
    {
        label: 'Miembros',
        value: props.stats.clientes,
        hint: 'Clientes registrados',
        gradient: 'from-[#b91c1c] to-[#dc2626]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4',
        href: route('clientes.index'),
    },
    {
        label: 'Ingresos del mes',
        value: formatearPrecio(props.stats.ingresosMes),
        hint: 'Inscripciones confirmadas',
        gradient: 'from-slate-900 to-slate-700',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        href: route('pedidos.index'),
    },
]);
</script>

<template>
    <Head title="Inicio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">
                Panel de control
            </h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <!-- Hero -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#dc2626] to-[#f97316] p-8 text-white shadow-xl shadow-orange-500/20 sm:p-10"
            >
                <div
                    class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"
                ></div>
                <div
                    class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-orange-300/20 blur-2xl"
                ></div>
                <div class="relative">
                    <p class="text-sm font-medium uppercase tracking-widest text-white/70">
                        Bienvenido a tu sistema
                    </p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">
                        Hola<span v-if="userFirstName">, {{ userFirstName }}</span> 💪
                    </h1>
                    <p class="mt-3 max-w-2xl text-base text-white/85">
                        Este es el panel de
                        <span class="font-semibold">{{ businessName }}</span>.
                        Aquí puedes administrar tus membresías, inscripciones, miembros
                        y las conversaciones que tu bot de WhatsApp atiende por ti.
                    </p>
                </div>
            </section>

            <!-- Stat cards -->
            <section>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="card in cards"
                        :key="card.label"
                        :href="card.href"
                        class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        <div class="flex items-start justify-between">
                            <span
                                :class="[
                                    'flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-md',
                                    card.gradient,
                                ]"
                            >
                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :d="card.icon"
                                    />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-4 text-3xl font-extrabold text-slate-800">
                            {{ card.value }}
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-600">
                            {{ card.label }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ card.hint }}</p>
                        <p class="mt-3 text-xs font-semibold text-[#dc2626] opacity-0 transition group-hover:opacity-100">
                            Ver / Administrar →
                        </p>
                    </Link>
                </div>
            </section>

            <!-- Welcome / next steps -->
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm lg:col-span-2"
                >
                    <h3 class="text-lg font-bold text-slate-800">
                        Tu bot de WhatsApp está listo
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        En cuanto vincules tu número en
                        <Link :href="route('conectar')" class="font-semibold text-[#dc2626] hover:underline">Conectar WhatsApp</Link>,
                        <span class="font-semibold text-slate-800">{{ businessName }}</span>
                        empezará a recibir mensajes, mostrar tus membresías y registrar
                        inscripciones automáticamente.
                    </p>
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-700">
                                Configura tus membresías
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Edita precios y descripciones en Membresías.
                            </p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-700">
                                Revisa las conversaciones
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Da seguimiento a cada chat en Conversaciones.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white shadow-sm"
                >
                    <div>
                        <h3 class="text-lg font-bold">¿Necesitas ayuda?</h3>
                        <p class="mt-2 text-sm text-slate-300">
                            Estamos para acompañarte. Cualquier ajuste o nueva función
                            que necesites, lo resolvemos por ti.
                        </p>
                    </div>
                    <p class="mt-6 text-xs text-slate-400">
                        Plataforma impulsada por
                        <span class="font-semibold text-slate-200">Overcloud</span>
                    </p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
