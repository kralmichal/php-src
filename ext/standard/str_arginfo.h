/* This is a generated file, edit str.stub.php instead.
 * Stub hash: e3cf1c276f496f80d1dc2949892e871124008a09 */

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Str_trim, 0, 1, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, characters, IS_STRING, 0, "\" \\n\\r\\t\\v\\x00\"")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Str_upper, 0, 1, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Str_lower arginfo_class_Str_upper

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Str_length, 0, 1, IS_LONG, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Str_contains, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, needle, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Str_startsWith, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, prefix, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Str_endsWith, 0, 2, _IS_BOOL, 0)
	ZEND_ARG_TYPE_INFO(0, string, IS_STRING, 0)
	ZEND_ARG_TYPE_INFO(0, suffix, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_METHOD(Str, trim);
ZEND_METHOD(Str, upper);
ZEND_METHOD(Str, lower);
ZEND_METHOD(Str, length);
ZEND_METHOD(Str, contains);
ZEND_METHOD(Str, startsWith);
ZEND_METHOD(Str, endsWith);

static const zend_function_entry class_Str_methods[] = {
	ZEND_ME(Str, trim, arginfo_class_Str_trim, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Str, upper, arginfo_class_Str_upper, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Str, lower, arginfo_class_Str_lower, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Str, length, arginfo_class_Str_length, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Str, contains, arginfo_class_Str_contains, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Str, startsWith, arginfo_class_Str_startsWith, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Str, endsWith, arginfo_class_Str_endsWith, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_Str(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "Str", class_Str_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES);

	return class_entry;
}
