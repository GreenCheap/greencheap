module.exports = [
    {
        entry: {
            installer: "./app/views/installer.vue",
            extensions: "./app/views/extensions",
            themes: "./app/views/themes",
            update: "./app/views/update",
            marketplace: "./app/views/marketplace",
            package:"./app/views/package"
        },
        output: {
            filename: "./app/bundle/[name].js",
        },
        module: {
            rules: [{ test: /\.vue$/, use: "vue-loader" }],
        },
    },
];
