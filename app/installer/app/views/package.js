import _ from "lodash";
import Version from "../lib/version";

const Package = {
    el: "#app",
    data() {
        return _.merge(
            {
                modalpkg: false,
                client: window.$client,
                output: "",
                isLoader: true,
                status: "",
            },
            window.$data
        );
    },

    methods: {
        getConvert(data) {
            return `data:image/svg+xml;base64,${window.btoa(data)}`;
        },

        getImage(data) {
            return `${this.system_api}/${data}`;
        },

        downloadPackage() {
            this.openModal();
            this.$http
                .get("admin/system/package/downloadpackage", {
                    params: {
                        package_name: this.package.package_name,
                    },
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
                .post("admin/system/package/install", { package: pkg, packagist: Boolean(packagist) }, null, { xhr: this.init() })
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

        init() {
            var vm = this;
            return {
                onprogress: function () {
                    vm.setOutput(this.responseText);
                },
            };
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
                this.$notify(this.$trans('"%title%" enabled.', { title: this.modalpkg.title }));
                document.location.assign(this.$url(`admin/system/package/${this.modalpkg.type === "greencheap-theme" ? "themes" : "extensions"}`));
            }, this.error);
        },

        openModal() {
            this.$set(this, "modalpkg", this.package);
            this.$refs.installDetail.open();
        },
    },
};

Vue.ready(Package);
export default Package;
