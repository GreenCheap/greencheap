<?php $view->script("marketplace", "installer:app/bundle/marketplace.js", "vue"); ?>

<section id="marketplace" v-cloak>
    <div class="uk-margin uk-flex uk-flex-between uk-flex-wrap">
        <div class="uk-flex uk-flex-middle uk-flex-wrap">
            <v-title :title="'{0} %count% Packages|{1} %count% Package|]1,Inf[ %count% Packages' | transChoice(count, {count:count})"></v-title>
            <div class="uk-search uk-search-default pk-search">
                <span uk-search-icon></span>
                <input class="uk-search-input" type="search" :placeholder="'Search Package' | trans" v-model="config.filter.search" />
            </div>
        </div>
    </div>

    <div class="uk-margin-large">
        <div class="uk-child-width-1-3@l uk-child-width-1-2@m uk-grid-match" uk-grid>
            <div v-for="(pkg, id) in pkgs" :key="id">
                <div class="uk-card uk-card-default tm-appstore-pkg">
                    <div v-if="pkg.data['image']" class="uk-card-media-top">
                        <img :data-src="getImage(pkg.data.image.src)" uk-img>
                    </div>
                    <div class="uk-card-body uk-card-small">
                        <div class="uk-flex uk-flex-middle uk-flex-between">
                            <div class="uk-flex uk-flex-middle">
                                <div v-if="pkg.data.icon" class="tm-appstore-pkg-icon" :style="'background-image: url('+getConvert(pkg.data.icon)+')'"></div>
                                <h3 class="uk-h5 uk-margin-remove">{{pkg.title}}</h3>
                            </div>
                            <div>
                                <div v-if="pkg.installed">
                                    <button v-show="!pkg.installed.update" class="uk-button uk-button-primary uk-button-small" disabled>{{ 'Installed'| trans }}</button>
                                    <a v-show="pkg.installed.update" :href="$url.route('admin/system/appstore/package', {name:pkg.package_name})" class="uk-button uk-button-secondary uk-button-small">{{ 'Update' | trans }}</a>
                                </div>
                                <div v-else>
                                    <a :href="$url.route('admin/system/appstore/package', {name:pkg.package_name})" class="uk-button uk-button-primary uk-button-small">{{'Install' | trans}}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <v-notfound v-show="!pkgs.length" :title="'Not found package' | trans"></v-notfound>

</section>
