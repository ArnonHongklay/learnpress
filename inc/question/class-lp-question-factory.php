<?php

/**
 * Class LP_Question_Factory
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Question_Factory {

	/**
	 * Hold the javascript template
	 *
	 * @var array
	 */
	protected static $_templates = array();

	/**
	 * Hold the list of question instances we have got
	 *
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Get the class instance for question
	 *
	 * @param bool  $the_question
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function get_question( $the_question = false, $args = array() ) {

		$the_question = self::get_question_object( $the_question );

		if ( !$the_question ) {
			return false;
		}
		$classname = self::get_question_class( $the_question, $args );
		if ( !class_exists( $classname ) ) {
			$classname = 'LP_Question_True_Or_False';
		}
		if ( is_array( $args ) ) {
			ksort( $args );
			$args_str = serialize( $args );
		} else {
			$args_str = $args;
		}

		$the_id = md5( $classname . $the_question->ID . '_' . $args_str );
		if ( empty( self::$_instances[$the_id] ) ) {
			self::$_instances[$the_id] = new $classname( $the_question, $args );
		}

		return self::$_instances[$the_id];
	}

	/**
	 * @param  string
	 *
	 * @return string|false
	 */
	public static function get_class_name_from_question_type( $type ) {
		return $type ? 'LP_Question_' . implode( '_', array_map( 'ucfirst', preg_split( '/-|_/', $type ) ) ) : false;
	}

	/**
	 * Get the class for a question from question object
	 *
	 * @param       $the_question
	 * @param array $args
	 *
	 * @return mixed
	 */
	public static function get_question_class( $the_question, $args = array() ) {
		$question_id = absint( $the_question->ID );

		if ( !empty( $args['type'] ) ) {
			$question_type = $args['type'];
		} else {
			$question_type = self::get_question_type( $question_id, $args );
		}
		$classname = self::get_class_name_from_question_type( $question_type );

		return apply_filters( 'learn_press_question_class', $classname, $question_type, $question_id );
	}

	/**
	 * Get the question object
	 *
	 * @param  mixed $the_question
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	public static function get_question_object( $the_question ) {
		if ( false === $the_question ) {
			$the_question = $GLOBALS['post'];
		} elseif ( is_numeric( $the_question ) ) {
			$the_question = get_post( $the_question );
		} elseif ( $the_question instanceof LP_Question ) {
			$the_question = get_post( $the_question->id );
		} elseif ( isset( $the_question->ID ) ) {
			$the_question = get_post( $the_question->ID );
		} elseif ( !( $the_question instanceof WP_Post ) ) {
			$the_question = false;
		}

		return apply_filters( 'learn_press_question_object', $the_question );
	}

	public static function get_question_type( $the_question, $args = array() ) {
		$type   = '';
		$the_id = 0;
		if ( !empty( $args['type'] ) ) {
			$type = $args['type'];
		} else {
			if ( is_numeric( $the_question ) ) {
				$type   = get_post_meta( $the_question, '_lp_type', true );
				$the_id = $the_question;
			} else if ( $the_question instanceof LP_Question ) {
				$type   = get_post_meta( $the_question->id, '_lp_type', true );
				$the_id = $the_question->id;
			} else if ( isset( $the_question->ID ) ) {
				$type   = get_post_meta( $the_question->ID, '_lp_type', true );
				$the_id = $the_question->ID;
			} else {
				$options = (array) $the_question;
				if ( isset( $options['type'] ) ) {
					$type = $options['type'];
				}
			}
		}
		if ( !$type && $the_id ) {
			$type = 'true_or_false';
			update_post_meta( $the_id, '_lp_type', $type );
		}
		return $type;
	}

	static function init() {

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
			add_action( 'save_post', array( __CLASS__, 'save' ) );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'admin_template' ) );
			add_action( 'learn_press_convert_question_type', array( __CLASS__, 'convert_question' ), 5, 4 );
			add_filter( 'learn_press_question_answers_data', array( __CLASS__, 'sanitize_answers' ), 10, 3 );

		} else {

		}
		add_action( 'learn_press_load_quiz_question', array( __CLASS__, 'save_question_if_needed' ), 100, 3 );
		add_action( 'learn_press_user_finish_quiz', array( __CLASS__, 'save_question' ), 100, 2 );

		LP_Question_Factory::add_template( 'multi-choice-option', LP_Question_Multi_Choice::admin_js_template() );
		LP_Question_Factory::add_template( 'single-choice-option', LP_Question_Single_Choice::admin_js_template() );

		do_action( 'learn_press_question_factory_init', __CLASS__ );
	}

	static function save_question( $quiz_id, $user_id ){
		self::save_question_if_needed( null, $quiz_id, $user_id );
	}

	/**
	 * Save question answer
	 *
	 * @param int
	 * @param int
	 * @param int
	 *
	 * @return bool
	 */
	static function save_question_if_needed( $question_id, $quiz_id, $user_id ) {
		$save_id  = learn_press_get_request( 'save_id' );
		$question = $save_id ? LP_Question_Factory::get_question( $save_id ) : false;
		if ( $question ) {
			$question_answer = null;
			$question_data   = isset( $_REQUEST['question_answer'] ) ? $_REQUEST['question_answer'] : array();
			if ( is_string( $question_data ) ) {
				parse_str( $question_data, $question_answer );
			} else {
				$question_answer = $question_data;
			}
			$question_answer = array_key_exists( 'learn-press-question-' . $save_id, $question_answer ) ? $question_answer['learn-press-question-' . $save_id] : '';

			$question->save_user_answer( $question_answer, $quiz_id );
			do_action( 'learn_press_save_user_question_answer', $question_answer, $save_id, $quiz_id, $user_id, true );
		}
		return $question;
	}

	static function sanitize_answers( $answers, $posted, $q ) {
		$func = "_sanitize_{$q->type}_answers";
		if ( is_callable( array( __CLASS__, $func ) ) ) {
			return call_user_func_array( array( __CLASS__, $func ), array( $answers, $q ) );
		}
		return $answers;
	}

	protected static function _sanitize_multi_choice_answers( $answers, $q ) {
		$size = sizeof( $answers );
		if ( $size == 0 ) {
			$answers = $q->get_default_answers();
		}
		$answers     = array_values( $answers );
		$has_checked = false;
		foreach ( $answers as $k => $answer ) {
			if ( empty( $answer['answer_data']['is_true'] ) || $answer['answer_data']['is_true'] != 'yes' ) {
				$answers[$k]['answer_data']['is_true'] = 'no';
				continue;
			}
			$has_checked = true;
		}
		if ( !$has_checked ) {
			$answers[0]['answer_data']['is_true'] = 'yes';
		}
		return $answers;
	}

	protected static function _sanitize_true_or_false_answers( $answers, $q ) {
		$size = sizeof( $answers );
		if ( $size > 2 ) {
			$answers = array_slice( $answers, 0, 2 );
		} elseif ( $size == 1 ) {
			$answers[] = array(
				'is_true' => 'no',
				'value'   => learn_press_uniqid(),
				'text'    => __( 'Option', 'learn_press' )
			);
		} elseif ( $size == 0 ) {
			return $answers;
		}
		$answers     = array_values( $answers );
		$has_checked = false;
		foreach ( $answers as $k => $answer ) {
			if ( $has_checked || empty( $answer['answer_data']['is_true'] ) || $answer['answer_data']['is_true'] != 'yes' ) {
				$answers[$k]['answer_data']['is_true'] = 'no';
				continue;
			}
			$has_checked = true;
		}
		if ( !$has_checked ) {
			$answers[0]['answer_data']['is_true'] = 'yes';
		}
		return $answers;
	}

	protected static function _sanitize_single_choice_answers( $answers, $q ) {
		$size = sizeof( $answers );
		if ( $size == 0 ) {
			$answers = $q->get_default_answers();
		}
		$answers     = array_values( $answers );
		$has_checked = false;
		foreach ( $answers as $k => $answer ) {
			if ( $has_checked || empty( $answer['answer_data']['is_true'] ) || $answer['answer_data']['is_true'] != 'yes' ) {
				$answers[$k]['answer_data']['is_true'] = 'no';
				continue;
			}
			$has_checked = true;
		}
		if ( !$has_checked ) {
			$answers[0]['answer_data']['is_true'] = 'yes';
		}
		return $answers;
	}

	static function admin_assets() {
		LP_Admin_Assets::enqueue_style( 'learnpress-question', learn_press_plugin_url( 'assets/css/admin/meta-box-question.css' ) );
		LP_Admin_Assets::enqueue_script( 'learnpress-question', learn_press_plugin_url( 'assets/js/admin/meta-box-question.js' ), array( 'jquery', 'jquery-ui-sortable' ) );
	}

	/**
	 * Get all type of questions
	 *
	 * @return mixed
	 */
	public static function get_types() {
		$types = array(
			'true_or_false' => __( 'True Or False', 'learn_press' ),
			'multi_choice'  => __( 'Multi Choice', 'learn_press' ),
			'single_choice' => __( 'Single Choice', 'learn_press' )
		);
		return apply_filters( 'learn_press_question_types', $types );
	}

	static function admin_template() {
		/*$questions = self::get_types();
		$method    = is_admin() ? 'admin_js_template' : 'frontend_js_template';

		if ( $questions ) foreach ( $questions as $type ) {
			$question = self::get_classname_from_question_type( $type );
			if ( is_callable( array( $question, $method ) ) ) {
				$template = call_user_func( array( $question, $method ) );
				printf( '<script id="tmpl-%s" type="text/html">%s</script>', $id, $template );
			}
		}*/
		foreach ( self::$_templates as $id => $content ) {
			printf( '<script id="tmpl-%s" type="text/html">%s</script>', $id, $content );
		}
	}

	static function save( $post_id ) {

		if ( wp_is_post_revision( $post_id ) )
			return;
		if ( !in_array( get_post_type( $post_id ), array( 'lp_quiz', 'lp_question' ) ) ) {
			return;
		}
		// prevent loop
		remove_action( 'save_post', array( __CLASS__, 'save' ) );
		if ( !empty( $_POST['learn_press_question'] ) ) {
			foreach ( $_POST['learn_press_question'] as $the_id => $post_data ) {
				( $question = self::get_question( $the_id ) ) && $question->save( $post_data );
			}
		}
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	static function add_template( $id, $content ) {
		self::$_templates[$id] = $content;
	}

	static function convert_question( $id, $from, $to, $data ) {
		if ( !empty( $data['learn_press_question'] ) && !empty( $data['learn_press_question'][$id] ) ) {
			$post_data = $data['learn_press_question'][$id];
		} else {
			$post_data = array();
		}
		if ( $question = self::get_question( $id ) ) {
			update_post_meta( $question->id, '_lp_type', $to );
			$question->type = $to;
			$question->save( $post_data );
		}
		return;
		switch ( $from ) {
			case 'true_or_false':
			case 'single_choice':
				if ( $to == 'multi_choice' ) {

				}
				break;
			case 'multi_choice':
				$count       = 0;
				$true_option = 0;
				if ( $to == 'true_or_false' ) {
					$first_option         = reset( $question->answers );
					$check_seconds_option = false;
					if ( $first_option['is_true'] != 'yes' ) {
						$check_seconds_option = true;
					}
					foreach ( $question->answers as $answer ) {
						$count ++;
						if ( $answer['is_true'] == 'yes' ) {
							$true_option ++;
						}
						if ( $true_option > 1 ) {
							$answer['is_true'] = 'no';
						}
						if ( $count == 2 && $check_seconds_option ) {
							$answer['is_true'] = 'yes';
						}
						$wpdb->update(
							$wpdb->learnpress_question_answers,
							array(
								'answer_data' => maybe_serialize( $answer )
							),
							array( 'question_answer_id' => $answer['id'] ),
							array( '%s' )
						);

						if ( $count >= 2 ) {
							break;
						}
					}
				} elseif ( $to == 'single_choice' ) {
					foreach ( $question->answers as $answer ) {
						if ( $answer['is_true'] == 'yes' ) {
							$true_option ++;
						}
						if ( $true_option > 2 ) {
							$answer['is_true'] = 'no';
						}

						$wpdb->update(
							$wpdb->learnpress_question_answers,
							array(
								'answer_data' => maybe_serialize( $answer )
							),
							array( 'question_answer_id' => $answer['id'] ),
							array( '%s' )
						);
					}
				}
		}
		update_post_meta( $question->id, '_lp_type', $to );
	}
}

LP_Question_Factory::init();