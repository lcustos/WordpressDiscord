<?php
/*
Plugin Name: Notif Discord Plugin
Description: This plugin send a discord message when a new comment is posted on a wordpress page
Author: CUSTOS Lucas & MONRIBOT Lucas
*/
//Initialisation of all the options
function create_option() {
    add_option( 'webhook' );
    add_option( 'bot_message' );
    add_option( 'bot_mention' );
}

register_activation_hook( __FILE__, 'create_option' );
//Send to Discord
function discord_notif( $comment_ID, $comment_approved ) {
    if ( 1 === $comment_approved ) {
        $comment   = get_comment( $comment_ID );
        $timestamp = date( "c", strtotime( "now" ) );
        $author    = $comment->comment_author;
        $bot_name    = get_option( 'bot_name' ) == "" ? "Bot" : get_option( 'bot_name' );
        $bot_content = get_option( 'bot_message' ) == "" ? "Content" : get_option( 'bot_message' );
        $bot_comment = get_option( 'bot_comment' ) == "" ? "Comment" : get_option( 'bot_comment' );
        switch ( get_option( 'bot_mention' ) ) {
            case "everyone":
                $bot_content = '@everyone ' . $bot_content;
                break;
            case "abonnes":
                $bot_content = '@abonnes ' . $bot_content;
                break;
            default:
                break;
        }
// Set data under json form to send to discord as en embeded message
        $json_data = json_encode( [
            "username" => $bot_name,
            "content"  => $bot_content,
            "embeds" => [
                [
                    "title" => "Comment sent in : " . get_the_title( $comment->comment_post_ID ),
                    "description" => $bot_comment . ": " . $comment->comment_content,
                    "timestamp" => $timestamp,
                    "color" => hexdec( "b4ac57" ),
                    "author" => [
                        "name" => "Author" . " : " . ucfirst( $author ),
                    ],
                ]
            ]
        ],);
        $ch = curl_init( get_option( 'webhook' ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $response = curl_exec( $ch );
        curl_close( $ch );
    }
}
add_action( 'comment_post', 'discord_notif', 10, 2 );
//Admin Dashboard Menu
function notification_admin_menu() {
    add_menu_page( 'Discord', 'Discord', 'manage_options', 'notifications-admin-menu-discord', 'menu_options', 'dashicons-bell', 2 );
}
add_action( 'admin_menu', 'notification_admin_menu' );
//Display the admin dashboard on wordpress
function menu_options() {
    ?>
    <form action="admin.php?page=notifications-admin-menu-discord" method="post">
        <div class="wrapper">
            <div>
                <h1>
                    <?php esc_html_e( 'Discord WebHook', 'notif_discord' ); ?>
                </h1>
                <input type="text" name="webhook" minlength="32"
                       placeholder="<?php if ( get_option( 'webhook' ) != null ) {
                           echo get_option( 'webhook' );
                       } else echo "Entre webhook" ?>">
                <span style="font-size:16px">Webhook URL.</span>
            </div>
            <div>
                <h1>
                    <?php esc_html_e( 'Mentions', 'bot_everyone' ); ?>
                </h1>
                <select name="mention">
                    <option value="nothing">No one</option>
                    <option value="everyone">Everyone</option>
                    <option value="abonnes">Abonnes</option>
                </select>
                <span style="font-size:16px"> Choose who will get the notification.</span>
            </div>
        </div>
        <br>
        <input type="submit" name="submit" value="Save Settings" class="button-primary">
    <!-- Sete default values for the bot -->
        <?php
        $webhookurl  = "";
        $bot_name    = "Jarvis";
        $bot_message = ":pushpin: New Message :pushpin:";
        $bot_author  = "";
        $bot_mention = "";
        if ( isset( $_POST['submit'] ) ) {
            $webhookurl  = $_POST['webhook'];
            $bot_name    = $_POST['bot_name'];
            $bot_message = $_POST['bot_message'];
            $bot_author  = $_POST['bot_author'];
            $bot_mention = $_POST['mention'];
            echo "Settings save";
        }
        if ( $webhookurl != "" && $webhookurl != get_option( 'webhook' ) ) {
            $options = update_option( 'webhook', $webhookurl );
        }
        if ( $bot_name != "" && $bot_name != get_option( 'bot_name' ) ) {
            $options = update_option( 'bot_name', $bot_name );
        }
        if ( $bot_message != "" && $bot_message != get_option( 'bot_message' ) ) {
            $options = update_option( 'bot_message', $bot_message );
        }
        if ( $bot_author != "" && $bot_author != get_option( 'bot_author' ) ) {
            $options = update_option( 'bot_author', $bot_author );
        }
        if ( $bot_mention != "" && $bot_mention != get_option( 'bot_mention' ) ) {
            $options = update_option( 'bot_mention', $bot_mention );
        }
}