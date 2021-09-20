import Version from "../lib/version";

const marketplace = {
    el: "#marketplace",
    name: "Marketplace",
    data() {
        return _.merge(
            {
                pkgs: false,
                config: {
                    filter: this.$session.get("pkgs-atomy.filter", { status: 2, order: "download_count desc" })
                },
                pages: 0,
                count: "",
                modalpkg: false,
                client: window.$client,
                output: "",
                isLoader: true,
                status: ""
            },
            window.$data
        );
    },

    mixins: [
        require("../../../system/app/lib/client")
    ],

    mounted() {
        this.$watch("config.page", this.load, { immediate: true });
    },

    watch: {
        "config.filter": {
            handler(filter) {
                if (this.config.page) {
                    this.config.page = 0;
                } else {
                    this.load();
                }
                this.$session.set("pkgs-atomy.filter", filter);
            },
            deep: true
        },

        pkgs: {
            handler() {
                _.forEach(this.pkgs, (pkg, key) => {
                    const controll = _.find(this.installedPackages, (installedPkg) => {
                        if (installedPkg.name === pkg.package_name) {
                            return true;
                        }
                        return false;
                    });
                    this.pkgs[key] = _.merge(
                        {
                            installed: controll
                                ? {
                                    name: controll.name,
                                    version: controll.version,
                                    type: controll.type,
                                    update: this.checkVersion(pkg.version, controll.version, ">")
                                }
                                : false
                        },
                        this.pkgs[key]
                    );
                });
            },
            deep: true
        }
    },

    methods: {
        load() {
            this.clientResource("api/atomy/app_store_packages", {
                filter: _.merge({ app_type: this.app_type }, this.config.filter),
                page: this.config.page
            })
                .then((res) => {
                    const { data } = res;
                    this.$set(this, "pkgs", data.packages);
                    this.$set(this, "pages", data.pages);
                    this.$set(this, "count", data.count);
                })
                .catch((err) => {
                    this.$notify(err.body.error, "danger");
                });
        },

        setType(type) {
            this.config.filter.type = type;
        },

        checkVersion(version, version2, operator) {
            return Version.compare(version, version2, operator);
        },

        openModal(pkg) {
            this.$set(this, "modalpkg", pkg);
            this.$refs.modalDeatil.open();
        },
        
        getConvert(data){
            return `data:image/svg+xml;base64,${window.btoa(data)}`
        },

        getImage(data) {
            return `${this.system_api}/${data}`;
        },

        downloadPackage(e) {
            e.target.innerHTML = `<span class="uk-margin-right" uk-spinner></span>${e.target.text}`;
            this.$http
                .get("admin/system/package/downloadpackage", {
                    params: {
                        id: this.modalpkg.id,
                        type: this.modalpkg.type
                    }
                })
                .then((res) => {
                    const pkg = res.data.package;
                    this.doInstall(pkg);
                })
                .catch((err) => {
                    this.$notify(err.bodyText, "danger");
                });
        },

        doInstall(pkg, packages, onClose, packagist) {
            const self = this;
            return this.$http
                .post(
                    "admin/system/package/install",
                    { package: pkg, packagist: Boolean(packagist) },
                    {
                        progress(e) {
                            if (e.lengthComputable) {
                                self.$refs.modalDeatil.close();
                                self.$refs.installDetail.open();
                                self.output += "Starting\n\n";
                            }
                        }
                    }
                )
                .then((res) => {
                    const patt = new RegExp("^status=(.+)", "gm");
                    this.setOutput(res.bodyText);
                    const getStatusTest = patt.exec(res.bodyText);
                    this.status = getStatusTest[1];
                    this.isLoader = false;
                })
                .catch((err) => {
                    this.$notify(err.data, "danger");
                    this.close();
                });
        },

        setOutput(output) {
            const lines = output.split("\n");
            const match = lines[lines.length - 1].match(/^status=(success|error)$/);
            if (match) {
                this.status = match[1];
                delete lines[lines.length - 1];
                this.output += lines.join("\n");
            } else {
                this.output += output;
            }
        },

        cancelPkg() {
            this.modalpkg = false;
            location.reload();
        },

        enablePkg() {
            return this.$http.post("admin/system/package/enable", { name: this.modalpkg.package_name }).then(() => {
                this.$notify(this.$trans("\"%title%\" enabled.", { title: this.modalpkg.title }));
                document.location.assign(this.$url(`admin/system/package/${this.modalpkg.type === "greencheap-theme" ? "themes" : "extensions"}`));
            }, this.error);
        }
    }
};

export default marketplace;

Vue.ready(marketplace);
