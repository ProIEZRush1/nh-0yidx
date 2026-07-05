<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    plan: Object,
});

const form = useForm({
    nombre: props.plan.nombre,
    precio: props.plan.precio / 100,
    descripcion: props.plan.descripcion ?? '',
    activo: props.plan.activo,
    orden: props.plan.orden,
});

function guardar() {
    form.put(route('planes.update', props.plan.id));
}
</script>

<template>
    <Head title="Editar membresía" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Editar membresía</h1>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="guardar" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="nombre" value="Nombre de la membresía" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="precio" value="Precio mensual (MXN)" />
                    <TextInput id="precio" v-model="form.precio" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.precio" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="descripcion" value="Descripción" />
                    <textarea
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                    ></textarea>
                    <InputError :message="form.errors.descripcion" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="orden" value="Orden de aparición" />
                    <TextInput id="orden" v-model="form.orden" type="number" min="0" class="mt-1 block w-full" />
                    <InputError :message="form.errors.orden" class="mt-2" />
                </div>

                <label class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.activo" />
                    <span class="text-sm text-slate-600">Membresía activa (visible en el bot de WhatsApp)</span>
                </label>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Guardar cambios</PrimaryButton>
                    <Link :href="route('planes.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
