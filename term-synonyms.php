<?php
/*
Plugin Name: Term Synonyms
Plugin URI: http://www.github.com/KamataRyo/term-synonyms
Description: define synonymous relationships among terms.
Author: kamataryo
Version: 0.0.1
Author URI: http://www.github.com/KamataRyo/
*/



//Load required files
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'term-synonyms.class.php';


// instansiate the plugin classes
global $termSynonyms_instance;
define( 'TERM_SYNONYMS_POST_TYPE', 'termsynonym_manager' );
$termSynonyms_instance = new TermSynonyms( TERM_SYNONYMS_POST_TYPE );



#todos
# done- class内部からカスタム投稿タイプを作成
# done- 作った投稿タイプに対して全てのタクソノミーを有効化(register taxonomy hook)
# - プラグインの実行を、全てのtaxonomyのregisteredが終わった後にされる様にする
#   (後から全てのタクソノミーを集約してカスタム投稿タイプに付与するため)
# - synonymsをregisterするときにはslug, id, nameのオプションを持たせる(今はnameのみ)
# - synonymsを登録した時に、1つのリストの中で　重複していたらどうなる？
# - synonymリストのunion機能（少なくとも1つのtermが跨っている時、postをunionする）
