<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    pedidos: Array,
    filtros: Object,
    trialLocked: Boolean,
});

const buscar = ref(props.filtros?.buscar ?? '');

function buscarPedidos() {
    router.get(route('pedidos.index'), { buscar: buscar.value }, { preserveState: true, replace: true });
}

const estados = {
    nuevo: { label: 'Nuevo', class: 'bg-blue-100 text-blue-700' },
    pendiente_pago: { label: 'Pendiente de anticipo', class: 'bg-amber-100 text-amber-700' },
    confirmado: { label: 'Confirmado', class: 'bg-green-100 text-green-700' },
    cancelado: { label: 'Cancelado', class: 'bg-slate-100 text-slate-500' },
};

async function eliminar(pedido) {
    const result = await Swal.fire({
        title: `¿Eliminar inscripción de "${pedido.cliente}"?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (result.isConfirmed) {
        router.delete(route('pedidos.destroy', pedido.id));
    }
}
</script>

<template>
    <Head title="Inscripciones" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Inscripciones</h1>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <input
                    v-model="buscar"
                    @keyup.enter="buscarPedidos"
                    type="text"
                    placeholder="Buscar por nombre o teléfono..."
                    class="w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                />
                <Link
                    :href="route('pedidos.create')"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#dc2626] to-[#f97316] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/20 transition hover:opacity-90"
                >
                    + Nueva inscripción
                </Link>
            </div>

            <div
                v-if="trialLocked"
                class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-800"
            >
                🔒 En la versión de prueba las inscripciones se registran, pero confirmarlas oficialmente se activa con tu anticipo.
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Membresía</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="pedido in pedidos" :key="pedido.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ pedido.cliente }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ pedido.plan?.nombre ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ pedido.telefono }}</td>
                            <td class="px-6 py-4">
                                <span :class="estados[pedido.estado]?.class ?? 'bg-slate-100 text-slate-500'" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                    {{ estados[pedido.estado]?.label ?? pedido.estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('pedidos.edit', pedido.id)" class="font-semibold text-[#dc2626] hover:text-[#f97316]">Editar</Link>
                                <button @click="eliminar(pedido)" class="ml-4 font-semibold text-slate-400 hover:text-red-600">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="pedidos.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">No hay inscripciones registradas todavía.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
