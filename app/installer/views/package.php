<?php $view->script("package-appstore", "installer:app/bundle/package.js", "vue"); ?>

<div id="app">
    <div class="uk-child-width-1-2@m" uk-grid>
        <div class="uk-flex uk-flex-left@m uk-flex-center">
            <div class="uk-flex uk-flex-middle">
                <div v-if="package.data.icon" class="tm-appstore-pkg-icon" :style="'background-image: url('+getConvert(package.data.icon)+')'"></div>
                <div>
                    <v-title :title="package.title"></v-title>
                    <span class="uk-text-meta">{{package.data.description}}</span>
                </div>
            </div>
        </div>
        <div class="uk-flex uk-flex-right@m uk-flex-center">
            <div v-if="hasPackage">
                <button v-show="!isUpdate" class="uk-button uk-button-primary" disabled>{{ 'Installed'| trans }}</button>
                <button v-show="isUpdate" @click.prevent="downloadPackage" href="$.url('admin/system/appstore/package{/name}', {name:'hello'})" class="uk-button uk-button-secondary">{{ 'Update' | trans }}</button>
            </div>
            <div v-else>
                <button @click.prevent="downloadPackage" class="uk-button uk-button-primary">{{'Install' | trans}}</button>
            </div>
        </div>
    </div>

    <div uk-grid>
        <div class="uk-width-expand@m">
            <img :data-src="getImage(package.data['image']['src'])" width="100%" uk-img>
        </div>
        <div class="uk-width-medium@m">
            <ul class="uk-list uk-list-large uk-list-divider uk-text-muted uk-text-small">
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Type' | trans}}</span>
                    <span>{{package.app_type == 1 ? "Extension" : "Theme" | trans}}</span>
                </li>
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Package Name' | trans}}</span>
                    <span>{{package.package_name}}</span>
                </li>
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Lang Used' | trans}}</span>
                    <ul class="uk-grid-small uk-margin-remove" uk-grid>
                        <li v-for="(lang, langId) in package.data['percent_file']['groupBy']" :key="langId">
                            <img :title="`${langId}: %${lang}`" uk-tooltip :data-src="$url('app/system/modules/theme/assets/programing-language-icons/'+langId+'.svg')" width="15px" uk-img>
                        </li>
                    </ul>
                </li>
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Author' | trans}}</span>
                    <span>{{package.author}}</span>
                </li>
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Version' | trans}}</span>
                    <span>{{package.version}}</span>
                </li>
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Last Update' | trans}}</span>
                    <span>{{package.modified | date}}</span>
                </li>
                <li class="uk-flex uk-flex-between uk-flex-middle">
                    <span>{{'Created' | trans}}</span>
                    <span>{{package.date | date}}</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="uk-margin">
        <div v-if="package.content">
            <v-title :title="'Description' | trans"></v-title>
            <hr>
            <div v-html="package.content"></div>
        </div>
        <div class="uk-margin-large-top" v-if="package.changelog">
            <v-title :title="'Changelog' | trans"></v-title>
            <hr>
            <div v-html="package.changelog"></div>
        </div>
    </div>

    <v-modal v-if="package" ref="installDetail" :center="true" :options="{escClose:false , bgClose:false}">
        <div class="uk-modal-header">
            <h2 class="uk-modal-title">
                {{package.title}} {{package.version}}
            </h2>
        </div>
        <div class="uk-modal-body">
            <pre v-show="output" class="uk-margin" v-html="output"></pre>
            <div v-show="!isLoader && status === 'success'" class="uk-alert-success" uk-alert>
                <p>{{ 'Installed successfully.' | trans }}</p>
            </div>
            <div v-show="!isLoader && status === 'error'" class="uk-alert-danger" uk-alert>
                <p>{{ 'There was a problem installing.' | trans }}</p>
            </div>
        </div>
        <div class="uk-modal-footer uk-text-right">
            <div v-if="isLoader && modalpkg">
                <i uk-spinner></i>
                <span class="uk-margin-left">{{ 'Wait' | trans }}</span>
            </div>
            <button v-if="!isLoader" class="uk-button uk-button-default" type="button" @click.prevent="cancelPkg">{{ 'Cancel' | trans }}</button>
            <button v-if="!isLoader && status === 'success'" class="uk-button uk-button-primary" @click.prevent="enablePkg" type="button">{{ 'Enable Package' | trans }}</button>
        </div>
    </v-modal>
</div>