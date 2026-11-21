<?php
// Makes the AI chat behave as on WordPress before 7.0, where the AI Client does not exist.
add_filter( 'surveyjs_ai_chat_client_available', '__return_false' );
