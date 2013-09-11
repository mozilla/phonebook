<?php

@include_once('config-local.php');

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("memory_limit", "64M");

if (!defined('MEMCACHE_ENABLED'))
    define("MEMCACHE_ENABLED", true);

require_once("config.php");
require_once("functions.php");
require_once("constants.php");
require_once("FirePHPCore/fb.php");
ob_start();

if (class_exists("Memcache") && MEMCACHE_ENABLED) {
  $memcache_on = true;
} else {
  $memcache_on = false;
}

$ldapconn = get_ldap_connection();

if ($memcache_on) {
    $memcache = new Memcache;

    if (empty($memcache_servers))
        $memcache_servers = array('localhost:11211');

    if (!defined('MEMCACHE_PREFIX'))
        define('MEMCACHE_PREFIX', 'phonebook:');

    foreach ($memcache_servers as $mc_server) {
        list($host, $port) = explode(':', $mc_server, 2);
        $memcache->addServer($host, $port);
    }
}

/*
 * A `user' is an identifier that uniquely represents a user. This could be a 
 * user's alias or an email address, and is usually not the DN.
 */
abstract class AuthAdapter {
  // Returns a boolean indicating whether a `user' is valid.
  public abstract function check_valid_user($user);

  // Converts a `user' to a DN string.
  public abstract function user_to_dn($user);

  // Returns the email address of a DN.
  public abstract function dn_to_email($dn);

  public function user_to_email($user) {
    return $this->dn_to_email($this->user_to_dn($user));
  }

  // Returns a boolean indicating if a DN has phonebook admin rights.
  public abstract function is_phonebook_admin($ldapconn, $dn);
}

/*
 * The search adapter regulates all searching and filtering activity. It is
 * initialized with an LDAP connection.
 */
abstract class SearchAdapter {
  protected $ldapconn;

  public function __construct($ldapconn) {
    $this->ldapconn = $ldapconn;
  }

  /*
   * Wraps the actions of ldap_search(), ldap_sort(), and ldap_entries() all in
   * one go. If $attributes is not specified, it is assumed to be specified in
   * an array field called $fields. Sorts resulting entries by surname by 
   * default, unless $conf["ldap_sort_order"] exists.
   */
  public function query_users($filter, $base='', $attributes=NULL) {
    $attributes = $attributes ? $attributes : $this->fields;
    $search = ldap_search($this->ldapconn, $base, $filter, $attributes);
    ldap_sort($this->ldapconn, $search,
      $this->conf["ldap_sort_order"] ? $this->conf["ldap_sort_order"] : "sn"
    );
    return ldap_get_entries($this->ldapconn, $search);
  }

  /*
   * Takes a given search string and calls query_users() accordingly. May
   * optionally pass query_users() different filters or even bases, depending
   * on the search string.
   */
  abstract public function search_users($search);

  /*
   * A callback / hook to modify the returned LDAP data structure 
   * post-normalization. This can, for example, be used to inject the URL to
   * fetch an entry's picture.
   */
  abstract public function preprocess_entry(&$entry);
}

abstract class EditingAdapter {
  /*
   * A callback / hook to modify the incoming, submitted POST data upon the 
   * submission of the Edit Entry form.
   */
  abstract public function cook_incoming(&$new_user_data, $is_admin);
}

/*
 * TreeAdapter currently assumes that everyone is uniquely identified by an
 * email address.
 */
abstract class TreeAdapter {
  /*
   * An array of the following options:
   * - ldap_search_base: a string
   * - ldap_search_filter: a string
   * - ldap_search_attributes: an array of strings
   */
  public $conf;

  // Array of roots with which to begin traversal when printing.
  public $roots;

  /*
   * Returns a normalized data structure for an entry given a raw LDAP entry
   * data structure.
   */
  public abstract function process_entry($person);

  // Returns a string to be printed out for an item.
  public abstract function format_item(&$everyone, $email, $leaf=FALSE);

  // It's a better idea to override this and make good use of $everyone and/or
  // $people.
  public function sort_items($a, $b) {
    global $everyone;
    return strcmp($a, $b);
  }
}

