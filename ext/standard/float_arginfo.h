/* This is a generated file, edit float.stub.php instead.
 * Stub hash: 5e5b726c60a01f7c9cd70d1cefdde49dadf140f2 */

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Float_round, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, num, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, precision, IS_LONG, 0, "0")
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_class_Float_ceil, 0, 1, IS_DOUBLE, 0)
	ZEND_ARG_TYPE_INFO(0, num, IS_DOUBLE, 0)
ZEND_END_ARG_INFO()

#define arginfo_class_Float_floor arginfo_class_Float_ceil

#define arginfo_class_Float_abs arginfo_class_Float_ceil

ZEND_METHOD(Float, round);
ZEND_METHOD(Float, ceil);
ZEND_METHOD(Float, floor);
ZEND_METHOD(Float, abs);

static const zend_function_entry class_Float_methods[] = {
	ZEND_ME(Float, round, arginfo_class_Float_round, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Float, ceil, arginfo_class_Float_ceil, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Float, floor, arginfo_class_Float_floor, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_ME(Float, abs, arginfo_class_Float_abs, ZEND_ACC_PUBLIC|ZEND_ACC_STATIC)
	ZEND_FE_END
};

static zend_class_entry *register_class_Float(void)
{
	zend_class_entry ce, *class_entry;

	INIT_CLASS_ENTRY(ce, "Float", class_Float_methods);
	class_entry = zend_register_internal_class_with_flags(&ce, NULL, ZEND_ACC_FINAL|ZEND_ACC_NO_DYNAMIC_PROPERTIES);

	return class_entry;
}
