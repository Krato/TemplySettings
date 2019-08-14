<template>
    <div>
        <heading class="mb-6">{{ __('Website Settings') }}</heading>

        <div class="card">

            <div class="tabs-wrap border-b-2 border-40 w-full">
                <div class="tabs flex flex-row overflow-x-auto">
                    <button
                        class="py-5 px-8 border-b-2 focus:outline-none tab"
                        :class="[activeTab == tab.key ? 'text-grey-black font-bold border-primary': 'text-grey font-semibold border-40']"
                        v-for="(tab, key) in tabs"
                        :key="key"
                        @click="handleTabClick(tab, $event)">
                        {{ tab.name }}
                    </button>
                </div>
            </div>

            <template v-for="(tab, index) in tabs">
                <div
                    :ref="tab.key"
                    v-if="tab.key == activeTab"
                    :label="tab.name"
                    :key="'related-tabs-fields' + index"
                  >

                    <div v-if="tab.init">
                        <form autocomplete="off" v-on:submit.prevent="saveSettings">
                            <template v-for="setting in tab.fields">
                                <div :class="{'hidden' : setting.hide}">
                                <component :is="setting.vue" :resourceName="resource" :errors="errorData" :field="setting.field"></component>
                                </div>
                            </template>

                            <div class="bg-30 flex px-8 py-4">

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

            

            
        </div>
    </div>
</template>

<script>

import { Errors, HandlesValidationErrors } from 'laravel-nova';
export default {
    mixins: [HandlesValidationErrors],
    // props: ['field'],
    data: () => ({
        tabs: [],
        settings: [],
        reset_button: false,
        resource: 'temply-settings',
        working: false,
        errorData: [],
        activeTab: ''
    }),

    methods: {
        getSettings(){
            Nova.request().get('/infinety-es/temply-settings/get-settings').then(response => {
                this.settings = response.data.fields;

                let tabs = {};

                this.settings.forEach(tab => {
                    tabs[tab.key] = {
                      name: tab.name,
                      key: tab.key,
                      init: false,
                      description: tab.description,
                      fields: tab.options
                    };
                });

                this.tabs = tabs;
                this.handleTabClick(tabs[Object.keys(tabs)[0]]);
            })
        },

        saveSettings(){
            this.working = true

            let data = this.actionFormData();

            console.log(data)

            Nova.request({
                method: 'post',
                url: '/infinety-es/temply-settings/save-settings',
                data: data,
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

        handleTabClick(tab, event) {
            tab.init = true;
            this.activeTab = tab.key;
        },

        /**
         * Slugify
         * From: https://gist.github.com/mathewbyrne/1280286
         */
        slugify(text) {
          return text
            .toString()
            .toLowerCase()
            .replace(/\s+/g, "-") // Replace spaces with -
            .replace(/[^\w\-]+/g, "") // Remove all non-word chars
            .replace(/\-\-+/g, "-") // Replace multiple - with single -
            .replace(/^-+/, "") // Trim - from start of text
            .replace(/-+$/, ""); // Trim - from end of text
        },

        /**
         * Gather the action FormData for the given action.
         */
        actionFormData() {
            let dataForm = _.tap(new FormData(), formData => {
                _.each(this.tabs[this.activeTab].fields, setting => {
                    setting.field.fill(formData)
                })
            })

            dataForm.append('tab', this.activeTab);

            return dataForm;
        },
    },

    computed: {
        //
    },

    mounted() {
        this.errorData = this.errors;
        this.getSettings()

        // return false;
        // this.settings.name.helpText = this.__('Your website name');
        // this.help.email = this.__('Your email for contact page');
        
        
    },
}
</script>

<style>
<style lang="scss">

  .tabs::-webkit-scrollbar {
    height: 8px;
    border-radius: 4px;
  }
  .tabs::-webkit-scrollbar-thumb {
    background: #cacaca;
  }
  .tabs {
    white-space: nowrap;
    margin-bottom: -2px;
  }
  .card {
    box-shadow: none;
  }
  h1 {
    display: none;
  }
  .tab {
    padding-top: 1.25rem;
    padding-bottom: 1.25rem;
  }
  .default-search > div > .relative > .flex {
    justify-content: flex-end;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    margin-top: 0.75rem;
    margin-bottom: 0;
    > .mb-6 {
      margin-bottom: 0;
    }
  }
  .tab-content > div > .relative > .flex {
    justify-content: flex-end;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    position: absolute;
    top: 0;
    right: 0;
    transform: translateY(-100%);
    align-items: center;
    height: 62px;
    z-index: 1;
    > .mb-6 {
      margin-bottom: 0;
    }
    > .w-full {
      width: auto;
      margin-left: 1.5rem;
    }
  }

</style>
</style>
