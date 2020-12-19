import Vue from 'vue';

export default {
    state: {
        conversations: []
    },
    getters: {
        CONVERSATIONS: state => {
            return state.conversations.sort((a,b) => {
                return a.createdAt < b.createdAt;
            })
        } ,
        MESSAGES: state => conversationId => {
            return state.conversations.find(i => i.conversationId === conversationId).messages
        }
    },
    mutations: {
        SET_CONVERSATIONS: (state, payload) => {
            state.conversations = payload
        },

        SET_MESSAGES: (state, {conversationId , payload}) => {
           
            Vue.set(
                state.conversations.find(i => i.conversationId === conversationId),
                'messages',
                payload
            )
        },

        ADD_MESSAGE: (state, {conversationId, payload}) => {
            state.conversations.find(i => i.conversationId === conversationId).messages.push(payload);
        },

        SET_CONVERSATION_LAST_MESSAGE: (state, {conversationId, payload}) =>{
            let rs =state.conversations.find(i => i.conversationId === conversationId)
            rs.content = payload.content;
            rs.createdAt = payload.createdAt;
        }

    },
    actions: {
        GET_CONVERSATIONS: ({commit}) => {
            return fetch(`/conversations`)
                .then(result   => result.json())
                .then((result) => {
                    commit("SET_CONVERSATIONS", result)
                })
        },
        GET_MESSAGES: ({commit, getters},conversationId ) => {
            if (getters.MESSAGES(conversationId )=== undefined ){
                return fetch(`/messages/${conversationId}`)
                    .then(result   => result.json())
                    .then((result) => {
                        commit("SET_MESSAGES", {conversationId, payload: result})
                    });
            }
        },

        POST_MESSAGE: ({commit}, {conversationId, content}) => {
            let formData = new FormData();
            formData.append('content', content);
            return fetch(`/messages/${conversationId}`, {
                method: "POST",
                body:   formData
            })
                .then(result   => result.json())
                .then((result) => {
                    commit("ADD_MESSAGE", {conversationId, payload: result})
                    commit("SET_CONVERSATION_LAST_MESSAGE", {conversationId, payload: result})
                })
        }
    }

}