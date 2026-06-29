/*
   +----------------------------------------------------------------------+
   | Copyright © The PHP Group and Contributors.                          |
   +----------------------------------------------------------------------+
   | This source file is subject to the Modified BSD License that is      |
   | bundled with this package in the file LICENSE, and is available      |
   | through the World Wide Web at <https://www.php.net/license/>.        |
   |                                                                      |
   | SPDX-License-Identifier: BSD-3-Clause                                |
   +----------------------------------------------------------------------+
*/

/* Float — internal backing class for scalar float methods.
 *
 * Each method is static and takes the subject float as its first argument, returning a
 * new value. This class is the dispatch target for the scalar-objects compiler feature;
 * userland should not instantiate it.
 *
 * The curated set reuses PHP's existing math primitives, and — unlike Int — every method
 * returns exactly float (a float operation cannot overflow to another type the way
 * abs(PHP_INT_MIN) does), so all four are chainable guaranteed-float receivers:
 *   - Float::round(float, int): float — _php_math_round (half-up, matching round()).
 *   - Float::ceil(float): float / Float::floor(float): float — libc ceil/floor.
 *   - Float::abs(float): float — fabs.
 */

#include "php.h"
#include "php_math.h"
#include "float_arginfo.h"

#include <math.h>

zend_class_entry *float_ce;

/* {{{ Float::round(float $num, int $precision = 0): float */
PHP_METHOD(Float, round)
{
	double num;
	zend_long precision = 0;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_DOUBLE(num)
		Z_PARAM_OPTIONAL
		Z_PARAM_LONG(precision)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_DOUBLE(_php_math_round(num, (int) precision, PHP_ROUND_HALF_UP));
}
/* }}} */

/* {{{ Float::ceil(float $num): float */
PHP_METHOD(Float, ceil)
{
	double num;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_DOUBLE(num)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_DOUBLE(ceil(num));
}
/* }}} */

/* {{{ Float::floor(float $num): float */
PHP_METHOD(Float, floor)
{
	double num;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_DOUBLE(num)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_DOUBLE(floor(num));
}
/* }}} */

/* {{{ Float::abs(float $num): float */
PHP_METHOD(Float, abs)
{
	double num;

	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_DOUBLE(num)
	ZEND_PARSE_PARAMETERS_END();

	RETURN_DOUBLE(fabs(num));
}
/* }}} */

PHP_MINIT_FUNCTION(float) /* {{{ */
{
	/* Register via the generated registrar, then make it internal-only by renaming to the
	 * NUL-prefixed name (ZEND_STR_SCALAR_METHODS_FLOAT_CLASS_NAME). Mirrors str.c / int.c exactly:
	 * the desugar references this name and is compiled as an ordinary static call resolved by
	 * name through the call cache — opcache-safe under SHM and file_cache. */
	float_ce = register_class_Float();

	zend_string *internal_name = zend_string_init_interned(
		ZEND_STR_SCALAR_METHODS_FLOAT_CLASS_NAME, ZEND_STR_SCALAR_METHODS_FLOAT_CLASS_NAME_LEN, 1);
	zend_string *internal_key = zend_string_tolower_ex(internal_name, /* persistent */ true);
	internal_key = zend_new_interned_string(internal_key);

	zend_string *old_key = zend_string_tolower(float_ce->name);
	dtor_func_t orig_dtor = CG(class_table)->pDestructor;
	CG(class_table)->pDestructor = NULL;
	zend_hash_del(CG(class_table), old_key);
	CG(class_table)->pDestructor = orig_dtor;
	zend_string_release(old_key);

	zend_string_release_ex(float_ce->name, /* persistent */ true);
	float_ce->name = internal_name;
	zend_hash_add_ptr(CG(class_table), internal_key, float_ce);
	zend_alloc_ce_cache(float_ce->name);
	zend_string_release_ex(internal_key, /* persistent */ true);

	return SUCCESS;
}
/* }}} */
