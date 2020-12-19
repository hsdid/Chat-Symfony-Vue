<template>
     <div class="col-7 px-0">
        
      <div class="px-4 py-5 chat-box bg-white" ref="messagesBody">

        <div v-for="message in MESSAGES" v-bind:key="message.id">
             <Message :message="message"/>
        </div>
           

      </div>

     <Input />

    </div>
</template>

<script>
import Message from './Message';
import Input from './Input.vue';

export default {
    components: {Message,Input},
    computed: {
        MESSAGES () {
            return this.$store.getters.MESSAGES(this.$route.params.id);
        }
    },

    methods: {
        scrollDown() {
            this.$refs.messagesBody.scrollTop = this.$refs.messagesBody.scrollHeight;
        }
    },

    mounted() {
        console.log(this.$route.params.id);
        this.$store.dispatch("GET_MESSAGES", this.$route.params.id)
            .then(() => {
                this.scrollDown();
            })
    },

    watch: {
        MESSAGES: function (val) {
            this.$nextTick(() => {
                this.scrollDown();
            })
        }
    }
}
</script>