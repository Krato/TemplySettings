<template>
    <div>
        <heading class="mb-6">{{ __('Website Settings') }}</heading>


        <div class="card">
            <form autocomplete="off" v-on:submit.prevent="saveSettings">
                <template v-for="setting in settings">
                    <div :class="{'hidden' : setting.hide}">
                    <component :is="setting.vue" :resourceName="resource" :errors="errorData" :field="setting.field"></component>
                    </div>
                </template>

                <div class="bg-30 flex px-8 py-4">
               <!--      <button dusk="update-button" class="ml-auto btn btn-default btn-primary" :disabled="working">
                        
                    </button> -->

                    <button class="ml-auto btn btn-default btn-primary inline-flex items-center relative">
                        <span :class="{'invisible': working}">
                            {{ __('Save settings') }}
                        </span>

                        <span
                            v-if="working"
                            class="absolute"
                            style="top: 50%; left: 50%; transform: translate(-50%, -50%)"
                        >
                            <loader class="text-white" width="32" />
                        </span>
                    </button>

                </div>
            </form>
        </div>
    </div>
</template>

<script>

import { Errors, HandlesValidationErrors } from 'laravel-nova';
export default {
    mixins: [HandlesValidationErrors],
    // props: ['field'],
    data: () => ({
        settings: [],
        reset_button: false,
        resource: 'temply-settings',
        working: false,
        errorData: [],
    }),

    methods: {
        getSettings(){
            Nova.request().get('/infinety-es/temply-settings/get-settings').then(response => {
                this.settings = response.data.fields;
            })
        },

        saveSettings(){
            this.working = true
            Nova.request({
                method: 'post',
                url: '/infinety-es/temply-settings/save-settings',
                data: this.actionFormData(),
            })
                .then(response => {
                    this.$toasted.show(this.__('Settings saved! - Reloading page.'), { type: 'success' });
                    this.working = false
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                })
                .catch(error => {
                    this.working = false
                    if (error.response.status == 422) {
                        this.errorData = new Errors(error.response.data.errors)
                    }
                })
        },

        /**
         * Gather the action FormData for the given action.
         */
        actionFormData() {
            return _.tap(new FormData(), formData => {
                _.each(this.settings, setting => {
                    setting.field.fill(formData)
                })
            })
        },
    },

    computed: {
        //
    },

    mounted() {
        this.errorData = this.errors;
        this.getSettings()
        // this.settings.name.helpText = this.__('Your website name');
        // this.help.email = this.__('Your email for contact page');
    },
}
</script>

<style>
/* Scoped Styles */
</style>
