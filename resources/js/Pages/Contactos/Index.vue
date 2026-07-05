<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    contactos: Array,
    filtros: Object,
});

const buscar = ref(props.filtros?.buscar ?? '');

function buscarContactos() {
    router.get(route('contactos.index'), { buscar: buscar.value }, { preserveState: true, replace: true });
}

const pasos = {
    new: { label: 'Nuevo', class: 'bg-slate-100 text-slate-600' },
    choosing: { label: 'Eligiendo membresía', class: 'bg-blue-100 text-blue-700' },
    confirming: { label: 'Confirmando', class: 'bg-amber-100 text-amber-700' },
    done: { label: 'Completado', class: 'bg-green-100 text-green-700' },
    human: { label: 'Con un asesor', class: 'bg-purple-100 text-purple-700' },
};

async function eliminar(contacto) {
    const result = await Swal.fire({
        title: `¿Eliminar la conversación con "${contacto.name ?? contacto.phone}"?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (result.isConfirmed) {
        router.delete(route('contactos.destroy', contacto.id));
    }
}
</script>

<template>
    <Head title="Conversaciones de WhatsApp" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Conversaciones de WhatsApp</h1>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <input
                v-model="buscar"
                @keyup.enter="buscarContactos"
                type="text"
                placeholder="Buscar por nombre o teléfono..."
                class="w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
            />

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Contacto</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado del bot</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Inscripciones</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="contacto in contactos" :key="contacto.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ contacto.name ?? 'Sin nombre' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ contacto.phone }}</td>
                            <td class="px-6 py-4">
                                <span :class="pasos[contacto.step]?.class ?? 'bg-slate-100 text-slate-500'" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                    {{ pasos[contacto.step]?.label ?? contacto.step }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ contacto.pedidos_count ?? 0 }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('contactos.edit', contacto.id)" class="font-semibold text-[#dc2626] hover:text-[#f97316]">Ver / Editar</Link>
                                <button @click="eliminar(contacto)" class="ml-4 font-semibold text-slate-400 hover:text-red-600">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="contactos.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">Todavía no hay conversaciones de WhatsApp.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
