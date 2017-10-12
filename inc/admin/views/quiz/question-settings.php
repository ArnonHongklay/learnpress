<?php
/**
 * Question template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/answers' );
learn_press_admin_view( 'quiz/meta' );
?>

<script type="text/x-template" id="tmpl-lp-question-settings">
    <div class="question-settings" :class="question.open ? 'hide-if-js' : 'table-row'">
        <lp-question-answers :question="question"></lp-question-answers>
        <lp-question-meta :question="question"></lp-question-meta>
    </div>
</script>


<script type="text/javascript">
    (function (Vue, $store) {
        Vue.component('lp-question-settings', {
            template: '#tmpl-lp-question-settings',
            props: ['question', 'index']
        })
    })(Vue, LP_Quiz_Store)
</script>