<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    contacto: Object,
});

const form = useForm({
    name: props.contacto.name ?? '',
    step: props.contacto.step,
});

function guardar() {
    form.put(route('contactos.update', props.contacto.id));
}

function formatearPrecio(centavos) {
    return '$' + (centavos / 100).toLocaleString('es-MX', { minimumFractionDigits: 0 }) + ' MXN/mes';
}
</script>

<template>
    <Head title="Conversación de WhatsApp" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Conversación de WhatsApp</h1>
        </template>

        <div class="mx-auto max-w-2xl space-y-6">
            <form @submit.prevent="guardar" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="name" value="Nombre del contacto" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Teléfono" />
                    <p class="mt-1 text-sm text-slate-500">{{ contacto.phone }}</p>
                </div>

                <div>
                    <InputLabel for="step" value="Estado del bot" />
                    <select id="step" v-model="form.step" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                        <option value="new">Nuevo</option>
                        <option value="choosing">Eligiendo membresía</option>
                        <option value="confirming">Confirmando</option>
                        <option value="done">Completado</option>
                        <option value="human">Con un asesor (bot en silencio)</option>
                    </select>
                    <InputError :message="form.errors.step" class="mt-2" />
                    <p class="mt-2 text-xs text-slate-400">
                        Cambia el estado a "Nuevo" para que el bot reinicie la conversación con este contacto.
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Guardar cambios</PrimaryButton>
                    <Link :href="route('contactos.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
                </div>
            </form>

            <div v-if="contacto.pedidos?.length" class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">Historial de inscripciones</h2>
                <ul class="mt-4 space-y-3">
                    <li v-for="pedido in contacto.pedidos" :key="pedido.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
                        <span class="font-semibold text-slate-700">{{ pedido.plan?.nombre ?? 'Sin membresía' }}</span>
                        <span class="text-slate-500">{{ pedido.plan ? formatearPrecio(pedido.plan.precio) : '' }}</span>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ pedido.estado }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
