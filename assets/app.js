import Vue from 'vue';
import VueRouter from 'vue-router';
import store from "./store/store";

import Blank from "./components/Right/Blank";
import Right from "./components/Right/Right";
import App from "./components/App.vue";

Vue.use(VueRouter)

const routes = [
    {
        name: 'blank',
        path: '/',
        component: Blank
    },
    {
        name: 'conversation',
        path: '/conversation/:id',
        component: Right
    }
];

const router = new VueRouter({

    mode: "abstract",
    routes
})


new Vue({
    store,
    router,
    el: "#app",
    render: h => h(App)
})

router.replace('/')