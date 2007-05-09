/*
   +----------------------------------------------------------------------+
   | This library is free software; you can redistribute it and/or        |
   | modify it under the terms of the GNU Lesser General Public           |
   | License as published by the Free Software Foundation; either         |
   | version 2.1 of the License, or (at your option) any later version.   | 
   |                                                                      |
   | This library is distributed in the hope that it will be useful,      |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
   | Lesser General Public License for more details.                      | 
   |                                                                      |
   | You should have received a copy of the GNU Lesser General Public     |
   | License in the file LICENSE along with this library;                 |
   | if not, write to the                                                 | 
   |                                                                      |
   |   Free Software Foundation, Inc.,                                    |
   |   59 Temple Place, Suite 330,                                        |
   |   Boston, MA  02111-1307  USA                                        |
   +----------------------------------------------------------------------+
   | Authors: Hartmut Holzgraefe <hartmut@php.net>                        |
   +----------------------------------------------------------------------+
*/

/* $ Id: $ */ 

#include "php_uuid.h"

#if HAVE_UUID

/* {{{ Class definitions */

/* {{{ Class uuid */

static zend_class_entry * uuid_ce_ptr = NULL;

/* {{{ Methods */


/* {{{ proto void __construct([mixed uuid])
   */
PHP_METHOD(uuid, __construct)
{
	zend_class_entry * _this_ce;
	zval * _this_zval;
	php_obj_uuid *payload;
	zval * uuid = NULL;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z/", &uuid) == FAILURE) { 
		return;
	}

	_this_zval = getThis();
	_this_ce = Z_OBJCE_P(_this_zval);

	payload = (php_obj_uuid *) zend_object_store_get_object(_this_zval TSRMLS_CC);
	do {

		int uuid_type = UUID_TYPE_INVALID;

		switch (uuid ? Z_TYPE_P(uuid) : IS_NULL) {
		  case IS_STRING:       
			if (uuid_parse(Z_STRVAL_P(uuid), *(payload->data))) {
			  php_error_docref(NULL TSRMLS_CC, E_WARNING,
							   "Invalid UUID string, using the NULL UUID instead");
			  uuid_clear(*(payload->data));
			}
			return;

		  case IS_LONG:
			uuid_type = Z_LVAL_P(uuid);
			break;

		  case IS_NULL:
			uuid_type = UUID_TYPE_DEFAULT;
			break;

		  default:
			uuid_type = UUID_TYPE_INVALID;
			break;
		}

		switch(uuid_type) {
		  case UUID_TYPE_DCE_TIME:
			uuid_generate_time(*(payload->data));
			break;
		  case UUID_TYPE_DCE_RANDOM:
			uuid_generate_random(*(payload->data));
			break;
		  case UUID_TYPE_DEFAULT:
			uuid_generate(*(payload->data));
			break;
		  case UUID_TYPE_NULL:
			uuid_clear(*(payload->data));
			break;
		  default:
			php_error_docref(NULL TSRMLS_CC, E_WARNING,
							 "Unknown/invalid UUID type '%d' requested, using default type instead",
							 uuid_type);
			uuid_generate(*(payload->data));
			break;
		}
	} while(0);
}
/* }}} __construct */



/* {{{ proto string __toString()
   */
PHP_METHOD(uuid, __toString)
{
	zend_class_entry * _this_ce;
	php_obj_uuid *payload;
	zval * _this_zval = NULL;

	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "O", &_this_zval, uuid_ce_ptr) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);

	payload = (php_obj_uuid *) zend_object_store_get_object(_this_zval TSRMLS_CC);
	do {

		char uuid_str[37];

		uuid_unparse(*(payload->data), uuid_str);

		RETURN_STRING(uuid_str, 1);
	} while(0);
}
/* }}} __toString */



/* {{{ proto string getType(void)
   */
PHP_METHOD(uuid, getType)
{
	zend_class_entry * _this_ce;
	php_obj_uuid *payload;
	zval * _this_zval = NULL;

	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "O", &_this_zval, uuid_ce_ptr) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);

	payload = (php_obj_uuid *) zend_object_store_get_object(_this_zval TSRMLS_CC);
	do {

		switch (uuid_type(*(payload->data))) {
		  case UUID_TYPE_DCE_TIME:
			RETURN_STRING("DCE_TIME", 1);
		  case UUID_TYPE_DCE_RANDOM:
			RETURN_STRING("DCE_RANDOM", 1);
		  default:
			RETURN_STRING("UNKNOWN", 1);
		}
	} while(0);
}
/* }}} getType */



/* {{{ proto string getVariant(void)
   */
PHP_METHOD(uuid, getVariant)
{
	zend_class_entry * _this_ce;
	php_obj_uuid *payload;
	zval * _this_zval = NULL;

	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "O", &_this_zval, uuid_ce_ptr) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);

	payload = (php_obj_uuid *) zend_object_store_get_object(_this_zval TSRMLS_CC);
	do {

		if (uuid_is_null(*(payload->data))) {
		  RETURN_STRING("NULL", 1);
		}

		switch (uuid_variant(*(payload->data))) {
		  case UUID_VARIANT_NCS:
			RETURN_STRING("NCS", 1);
		  case UUID_VARIANT_DCE:
			RETURN_STRING("DCE", 1);
		  case UUID_VARIANT_MICROSOFT:
			RETURN_STRING("MICROSOFT", 1);
		  case UUID_VARIANT_OTHER:
			RETURN_STRING("OTHER", 1);
		  default:
			RETURN_STRING("UNKNOWN", 1);
		}
	} while(0);
}
/* }}} getVariant */


static zend_function_entry uuid_methods[] = {
	PHP_ME(uuid, __construct, NULL, /**/ZEND_ACC_PUBLIC)
	PHP_ME(uuid, __toString, NULL, /**/ZEND_ACC_PUBLIC)
	PHP_ME(uuid, getType, uuid__getType_args, /**/ZEND_ACC_PUBLIC)
	PHP_ME(uuid, getVariant, uuid__getVariant_args, /**/ZEND_ACC_PUBLIC)
	{ NULL, NULL, NULL }
};

/* }}} Methods */


static zend_object_handlers uuid_obj_handlers;

static void uuid_obj_free(void *object TSRMLS_DC)
{
	php_obj_uuid *intern = (php_obj_uuid *)object;
	
	uuid_t *data = intern->data;
	free(intern->data);

	efree(object);
}

static zend_object_value uuid_obj_create(zend_class_entry *class_type TSRMLS_DC)
{
	php_obj_uuid *intern;
	zval         *tmp;
	zend_object_value retval;

	intern = (php_obj_uuid *)emalloc(sizeof(php_obj_uuid));
	memset(intern, 0, sizeof(php_obj_uuid));
	intern->obj.ce = class_type;
	intern->data = (uuid_t *)malloc(sizeof(uuid_t));

	retval.handle = zend_objects_store_put(intern, NULL, (zend_objects_free_object_storage_t) uuid_obj_free, NULL TSRMLS_CC);
	retval.handlers = &uuid_obj_handlers;
	
	return retval;
}

static void class_init_uuid(void)
{
	zend_class_entry ce;

	INIT_CLASS_ENTRY(ce, "uuid", uuid_methods);
	ce.create_object = uuid_obj_create;
	uuid_ce_ptr = zend_register_internal_class(&ce);
	memcpy(&uuid_obj_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
	uuid_obj_handlers.clone_obj = NULL;
}

/* }}} Class uuid */

/* }}} Class definitions*/

/* {{{ uuid_functions[] */
function_entry uuid_functions[] = {
	PHP_FE(uuid_create         , NULL)
	PHP_FE(uuid_is_valid       , NULL)
	PHP_FE(uuid_compare        , NULL)
	PHP_FE(uuid_is_null        , NULL)
	PHP_FE(uuid_type           , NULL)
	PHP_FE(uuid_variant        , NULL)
	PHP_FE(uuid_time           , NULL)
	PHP_FE(uuid_mac            , NULL)
	{ NULL, NULL, NULL }
};
/* }}} */


/* {{{ uuid_module_entry
 */
zend_module_entry uuid_module_entry = {
	STANDARD_MODULE_HEADER,
	"uuid",
	uuid_functions,
	PHP_MINIT(uuid),     /* Replace with NULL if there is nothing to do at php startup   */ 
	PHP_MSHUTDOWN(uuid), /* Replace with NULL if there is nothing to do at php shutdown  */
	PHP_RINIT(uuid),     /* Replace with NULL if there is nothing to do at request start */
	PHP_RSHUTDOWN(uuid), /* Replace with NULL if there is nothing to do at request end   */
	PHP_MINFO(uuid),
	"0.1", 
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_UUID
ZEND_GET_MODULE(uuid)
#endif


/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(uuid)
{
	REGISTER_LONG_CONSTANT("UUID_VARIANT_NCS", UUID_VARIANT_NCS, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_VARIANT_DCE", UUID_VARIANT_DCE, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_VARIANT_MICROSOFT", UUID_VARIANT_MICROSOFT, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_VARIANT_OTHER", UUID_VARIANT_OTHER, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_DEFAULT", 0, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_TIME", UUID_TYPE_DCE_TIME, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_DCE", UUID_TYPE_DCE_RANDOM, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_NAME", UUID_TYPE_DCE_TIME, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_RANDOM", UUID_TYPE_DCE_RANDOM, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_NULL", -1, CONST_PERSISTENT | CONST_CS);
	REGISTER_LONG_CONSTANT("UUID_TYPE_INVALID", -42, CONST_PERSISTENT | CONST_CS);
	class_init_uuid();

	/* add your stuff here */

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION(uuid)
{

	/* add your stuff here */

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION(uuid)
{
	/* add your stuff here */

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION(uuid)
{
	/* add your stuff here */

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(uuid)
{
	php_info_print_box_start(0);
	php_printf("<p>UUID extension</p>\n");
	php_printf("<p>Version 0.1alpha (2006-06-24)</p>\n");
	php_printf("<p><b>Authors:</b></p>\n");
	php_printf("<p>Hartmut Holzgraefe &lt;hartmut@php.net&gt; (lead)</p>\n");
	php_info_print_box_end();
	/* add your stuff here */

}
/* }}} */


/* {{{ proto string uuid_create([int uuid_type])
  Generate a new UUID */
PHP_FUNCTION(uuid_create)
{
	long uuid_type = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|l", &uuid_type) == FAILURE) { 
		return;
	}
	do {
#line 100 "uuid-10.xml"
		uuid_t uuid;
		char uuid_str[37];

		switch(uuid_type) {
		  case UUID_TYPE_DCE_TIME:
			uuid_generate_time(uuid);
			break;
		  case UUID_TYPE_DCE_RANDOM:
			uuid_generate_random(uuid);
			break;
		  case UUID_TYPE_DEFAULT:
			uuid_generate(uuid);
			break;
		  default:
			php_error_docref(NULL TSRMLS_CC, 
							 E_WARNING,
							 "Unknown/invalid UUID type '%d' requested, using default type instead",
							 uuid_type);
			uuid_generate(uuid);
			break;        
		}

		uuid_unparse(uuid, uuid_str);

		RETURN_STRING(uuid_str, 1);
	} while(0);
}
/* }}} uuid_create */


/* {{{ proto bool uuid_is_valid(string uuid)
  Check whether a given UUID string is a valid UUID */
PHP_FUNCTION(uuid_is_valid)
{
	const char * uuid = NULL;
	int uuid_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) { 
		return;
	}
	do {
#line 133 "uuid-10.xml"
		uuid_t u; 
		RETURN_BOOL(0 == uuid_parse(uuid, u));
	} while(0);
}
/* }}} uuid_is_valid */


/* {{{ proto int uuid_compare(string uuid1, string uuid2)
  Compare two UUIDs */
PHP_FUNCTION(uuid_compare)
{
	const char * uuid1 = NULL;
	int uuid1_len = 0;
	const char * uuid2 = NULL;
	int uuid2_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &uuid1, &uuid1_len, &uuid2, &uuid2_len) == FAILURE) { 
		return;
	}
	do {
#line 162 "uuid-10.xml"
		uuid_t u1, u2;

		if(uuid_parse(uuid1, u1)) RETURN_FALSE;
		if(uuid_parse(uuid2, u2)) RETURN_FALSE;

		RETURN_LONG(uuid_compare(u1, u2));
	} while(0);
}
/* }}} uuid_compare */


/* {{{ proto bool uuid_is_null(string uuid)
  Check wheter an UUID is the NULL UUID 00000000-0000-0000-0000-000000000000 */
PHP_FUNCTION(uuid_is_null)
{
	const char * uuid = NULL;
	int uuid_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) { 
		return;
	}
	do {
#line 222 "uuid-10.xml"
		uuid_t u;

		if(uuid_parse(uuid, u)) RETURN_FALSE;

		RETURN_BOOL(uuid_is_null(u));
	} while(0);
}
/* }}} uuid_is_null */


/* {{{ proto int uuid_type(string uuid)
  Return the UUIDs type */
PHP_FUNCTION(uuid_type)
{
	const char * uuid = NULL;
	int uuid_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) { 
		return;
	}
	do {
#line 256 "uuid-10.xml"
		uuid_t u;

		if(uuid_parse(uuid, u)) RETURN_FALSE;

		if (uuid_is_null(u)) RETURN_LONG(UUID_TYPE_NULL);

		RETURN_LONG(uuid_type(u));
	} while(0);
}
/* }}} uuid_type */


/* {{{ proto int uuid_variant(string uuid)
  Return the UUIDs variant */
PHP_FUNCTION(uuid_variant)
{
	const char * uuid = NULL;
	int uuid_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) { 
		return;
	}
	do {
#line 290 "uuid-10.xml"
		uuid_t u;

		if(uuid_parse(uuid, u)) RETURN_FALSE;

		if (uuid_is_null(u)) RETURN_LONG(UUID_TYPE_NULL);

		RETURN_LONG(uuid_variant(u));
	} while(0);
}
/* }}} uuid_variant */


/* {{{ proto int uuid_time(string uuid)
  Extract creation time from a time based UUID as UNIX timestamp */
PHP_FUNCTION(uuid_time)
{
	const char * uuid = NULL;
	int uuid_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) { 
		return;
	}
	do {
#line 324 "uuid-10.xml"
		uuid_t u;

		if(uuid_parse(uuid, u))  RETURN_FALSE;
		if(uuid_variant(u) != 1) RETURN_FALSE;
		if(uuid_type(u) != 1)    RETURN_FALSE;

		RETURN_LONG(uuid_time(u, NULL));
	} while(0);
}
/* }}} uuid_time */


/* {{{ proto string uuid_mac(string uuid)
  Get UUID creator network MAC address */
PHP_FUNCTION(uuid_mac)
{
	const char * uuid = NULL;
	int uuid_len = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &uuid, &uuid_len) == FAILURE) { 
		return;
	}
	do {
#line 348 "uuid-10.xml"
		uuid_t u;
				char *uuid_str[37];
		
				if(uuid_parse(uuid, u))  RETURN_FALSE;
				if(uuid_variant(u) != 1) RETURN_FALSE;
				if(uuid_type(u) != 1)    RETURN_FALSE;
				if(uuid[10]&0x80)        RETURN_FALSE; // invalid MAC 
		
				uuid_unparse(u, uuid_str);
				RETURN_STRING(uuid_str+24, 1);
	} while(0);
}
/* }}} uuid_mac */

#endif /* HAVE_UUID */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
