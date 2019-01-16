Nova.booting((Vue, router) => {
    router.addRoutes([
        {
            name: 'temply-settings',
            path: '/temply-settings',
            component: require('./components/Tool'),
        },
    ])
})
