<?php
/**
 * Provides the email templates.
 *
 * This file is used to create the related email templates.
 *
 * @link       #
 * @since      1.1.0
 *
 * @package    content-collaboration-inline-commenting.
 */

class Commenting_Block_Email_Templates {

	/**
	 * Limiting the characters of a string.
	 *
	 * @param string $string The string that is going to be limiting.
	 * @param integer $limit The limiting value.
	 *
	 * @return void
	 */
	public function cf_limit_characters( $string, $limit = 100 ) {
		return strlen( $string ) > $limit ? substr( $string, 0, $limit ) . '...' : $string;
	}

	/**
	 * Resolved Thread Email Template.
	 *
	 * @param array $args Data to be used in the template.
	 *
	 * @return void
	 */
	public function cf_email_resolved_thread( $args ) {
		$html                      = $args['html'];
		$comments                  = $args['list_of_comments'];
		$site_title                = $args['site_title'];
		$commented_on_text                = $args['commented_on_text'];
		$current_user_email        = $args['current_user_email'];
		$current_user_display_name = $args['current_user_display_name'];

		if ( ! empty( $comments ) && is_array( $comments ) ) {

			$users_emails = array();
			$headers      = array( 'Content-Type: text/html; charset=UTF-8' );
			$html .= "<div class='commented_text'>" . $commented_on_text . "</div>";
			$html .= '<ul class="comment-list">';

			foreach ( $comments as $timestamp => $arr ) {

				if ( isset( $arr['status'] ) && 'permanent_draft' !== $arr['status'] && 'draft' !== $arr['status'] ) {
					$user_info      = get_userdata( $arr['userData'] );
					$username       = $user_info->display_name;
					$user_role      = implode( ', ', $user_info->roles );
					$users_emails[] = $user_info->user_email;
					$profile_url    = get_avatar_url( $user_info->user_email );
					$text_comment   = wp_kses( $arr['thread'], wp_kses_allowed_html( 'post' ) );

					$html .= "<li>
										<div class='comment-box-wrap'>
										<div class='avtar'><img src='" . esc_url( $profile_url ) . "' alt='avatar' /></div>
											<div class='comment-details'>
												<div class='commenter-name-role'>
													<h3 class='commenter-name'>" . esc_html( $username ) . "</h3>
													<span class='commenter-role'>( " . esc_html( ucwords( $user_role ) ) . " )</span>
												</div>
												<div class='comment'>" . $text_comment . "</div>
											</div>
										</div>
									   </li>";
				}
			}
			$html .= '</ul>';
			$html .= '<div class="cf-marked-resolved-by">';
			$html .= '<span class="icon-resolved"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
							  <g id="Group_22" data-name="Group 22" transform="translate(1 1)">
							    <circle id="Ellipse_4" data-name="Ellipse 4" cx="10" cy="10" r="10" fill="none" stroke="#6ac359" stroke-width="2"/>
							    <path id="Path_7" data-name="Path 7" d="M93.92,119.6l-3.593-3.664,1.353-1.327,2.252,2.3,5.621-5.621,1.34,1.34Z" transform="translate(-85.327 -105.288)" fill="#6ac359"/>
							  </g>
							</svg></span>';
			$html .= __( 'Marked as resolved by ', 'content-collaboration-inline-commenting' ) . '<a href="mailto:' . esc_attr( $current_user_email ) . '" title="' . esc_attr( $current_user_display_name ) . '" target="_blank"> ' . esc_html( $current_user_display_name ) . ' </a>' . '</div>';
			$html .= '</div>'; // .comment-box-body end
			$html .= '</div>'; // .comment-box end

			$users_emails = array_unique( $users_emails );
			if ( ( $key = array_search( $current_user_email, $users_emails, true ) ) !== false ) {
				unset( $users_emails[ $key ] );
			}

			// Notify Site Admin if setting enabled.
			$users_emails = $this->cf_email_notify_siteadmin( $users_emails );

			// Limit the page and site titles for Subject.
			$site_title = $this->cf_limit_characters( $site_title, 20 );
			$r_subject  = ! empty( $p_title ) ? $this->cf_limit_characters( $p_title, 30 ) . ' — ' . $site_title : $site_title;
			$r_subject  = sprintf( __( 'Comment Resolved — %s', 'content-collaboration-inline-commenting' ), $r_subject );

			wp_mail( $users_emails, $r_subject, $html, $headers );
		}
	}

	private function cf_email_notify_siteadmin( $users_emails ) {
		$cf_admin_notif = get_option( 'cf_admin_notif' );
		if ( '1' === $cf_admin_notif ) {
			$users_emails[] = get_option( 'admin_email' );
		}
		return $users_emails;
	}

	/**
	 * Add Comment / Reply Comment Email Template.
	 *
	 * @param array $args Contains all keys related to send the email.
	 *
	 * @return void
	 */
	public function cf_email_new_comments( $args ) {
		$current_user_data = get_user_by( 'ID', $args['commenter'] );
		$thread            = '';
		foreach ( $args['list_of_comments'] as $comment ) {
			if ( 'draft' === $comment['status'] ) {
				$thread .= $comment['thread'];
			}
		}

		$pattern = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
		preg_match_all( $pattern, $thread, $matches );
		$email_list = array_unique( $matches[0] );

		if ( ! empty( $args['list_of_comments'] ) ) {
			$comment_list_html = '<ul class="comment-list">';
			foreach ( $args['list_of_comments'] as $comment ) {
				$user_ID = $comment['userData'];
				if ( isset( $comment['status'] ) && 'permanent_draft' !== $comment['status'] && 'deleted' !== $comment['status'] && 'draft' !== $comment['status'] ) {
					$user_data         = get_user_by( 'ID', $user_ID );
					$user_role         = ucwords( implode( ', ', $user_data->roles ) );
					$comment_list_html .= "
                    <li>
                        <div class='comment-box-wrap'>
                            <div class='avtar'>
                                <img src='" . esc_url( get_avatar_url( $user_data->ID, [ 'size' => '32' ] ) ) . "' alt='" . esc_attr( $user_data->display_name ) . "'/>
                            </div>
                            <div class='comment-details'>
                                <div class='commenter-name-role'>
                                    <h3 class='commenter-name'>" . esc_html( $user_data->display_name ) . "</h3>
                                    <span class='commenter-role'>( " . esc_html( $user_role ) . " )</span>
                                </div>
                                <div class='comment'>" . wp_kses( $comment['thread'], wp_kses_allowed_html( 'post' ) ) . "</div>
                            </div>
                        </div>
                    </li>
                ";
				}
			}
			$comment_list_html .= '</ul>';
		}

		$assigned_to_who = '';
		if ( ! empty( $args['assign_to'] ) ) {
			$assinged_user   = get_user_by( 'ID', $args['assign_to'] );
			$assigned_to_who = "
                <div class='comment-assigned-to'>
                    <span class='icon-assign'>
                        <svg id='Group_31' data-name='Group 31' xmlns='http://www.w3.org/2000/svg' width='19.644' height='20' viewBox='0 0 19.644 20'>
                            <g id='Group_28' data-name='Group 28' transform='translate(2.21)'>
                            <path id='Path_11' data-name='Path 11' d='M149.786,160.469a10.107,10.107,0,0,1-7.123-2.907.885.885,0,0,1,0-1.279.885.885,0,0,1,1.275,0,8.254,8.254,0,0,0,5.78,2.439,7.905,7.905,0,0,0,5.776-2.436,8.236,8.236,0,0,0,0-11.632,8.253,8.253,0,0,0-5.779-2.438,8.032,8.032,0,0,0-5.779,2.438,1.047,1.047,0,0,1-1.255.018.771.771,0,0,1-.29-.564.949.949,0,0,1,.269-.73,9.992,9.992,0,0,1,7.126-2.909,10.107,10.107,0,0,1,7.124,2.907,9.761,9.761,0,0,1,2.912,7.128,10.1,10.1,0,0,1-2.907,7.124A9.619,9.619,0,0,1,149.786,160.469Z' transform='translate(-142.388 -140.469)' fill='#6ac359'/>
                            </g>
                            <g id='Group_29' data-name='Group 29' transform='translate(0 9.055)'>
                            <path id='Path_12' data-name='Path 12' d='M141.088,151.342a.909.909,0,1,1,0-1.818h5.727a.909.909,0,1,1,0,1.818Z' transform='translate(-140.178 -149.524)' fill='#6ac359'/>
                            </g>
                            <g id='Group_30' data-name='Group 30' transform='translate(4.564 4.705)'>
                            <path id='Path_13' data-name='Path 13' d='M148.645,155.834a.844.844,0,0,1-.638-.271.884.884,0,0,1,0-1.276l2.945-2.945h-5.3a.909.909,0,0,1,0-1.818h5.159l-2.8-2.8a.884.884,0,0,1,0-1.276.884.884,0,0,1,1.276,0l4.492,4.492a.8.8,0,0,1,.2.566.845.845,0,0,1-.271.639l-4.421,4.42A.841.841,0,0,1,148.645,155.834Z' transform='translate(-144.742 -145.174)' fill='#6ac359'/>
                            </g>
                        </svg>
                    </span>
                    Assigned to <a href='mailto:" . sanitize_email( $assinged_user->user_email ) . "' title='" . esc_attr( $assinged_user->display_name ) . "' class='commenter-name'>@" . esc_html( $assinged_user->display_name ) . "</a>
                </div>
            ";
		}

		$template = $args['html']; /*Common CSS added*/
		$template .= "
            <div class='comment-box new-comment'>
                <div class='comment-box-header'>
                    <p><span class='commenter-name'>".esc_html( $current_user_data->display_name )."</span> - mentioned you in a comment in the following page.</p>";

		if( empty( $args['post_title'] ) ) {
			$template .= "<h2 class='comment-page-title'><a href='".esc_url( $args['post_edit_link'] )."' target='_blank'>".esc_html( $args['post_title'] )."</a></h2>";
		}

		$template .= "
                </div>
                <div class='comment-box-body'>
                    <h2 class='head-with-icon'>
                        <span class='icon-comment'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='36.226' height='43.02' viewBox='0 0 36.226 43.02'>
                                <g id='Group_2' data-name='Group 2' transform='translate(-36.242 1.019)'>
                                    <path id='Path_1' data-name='Path 1' d='M64.607,30.769,52.29,40l0-5.88-1.37-.279a17.1,17.1,0,1,1,13.683-3.072Z' transform='translate(0 0)' fill='none' stroke='#4b1bce' stroke-width='2'/>
                                </g>
                            </svg>
                        </span>
                        Comments
                    </h2>
                    <div class='commented_text'>" . esc_html( $args['commented_text'] ) . "</div>
                    {$assigned_to_who}
                    {$comment_list_html}
                    <div class='view_reply'>
                        <div class='view_reply_btn'><a href='" . esc_url( $args['post_edit_link'] ) . "'>Click here</a> - View or reply to this comment</div>
                    </div>
                </div>
            </div>
        ";

		// Limit the page and site titles for Subject.
        $post_title = strlen( $args['post_title'] ) > 30 ? substr( $args['post_title'], 0, 30 ) . '...': $args['post_title'];
        $site_name  = strlen( $args['site_name'] ) > 20 ? substr( $args['site_name'], 0, 20 ) . '...'  : $args['site_name'];

		//    if( ! empty( $args['assign_to'] ) ) {
		//        $key = array_search( $args['assign_to'], $email_list, true );
		//        unset( $email_list[$key] );
		//    }

		// Notify Site Admin if setting enabled.
		$email_list = $this->cf_email_notify_siteadmin( $email_list );

		if ( ! empty( $email_list ) ) {
			$to      = $email_list;
			$subject = "New Comment - " . esc_html( $post_title ) . " - " . esc_html( $site_name );
			$body    = $template;
			$headers = 'Content-Type: text/html; charset=UTF-8';

			wp_mail( $to, $subject, $body, $headers );
		}

		if ( ! empty( $assinged_user ) ) {
			$assign_to      = $assinged_user->user_email;
			$assign_subject = "Assigned to you - " . esc_html( $post_title );
			$assign_body    = $template;
			$headers        = 'Content-Type: text/html; charset=UTF-8';
			wp_mail( $assign_to, $assign_subject, $assign_body, $headers );
		}
	}
}

