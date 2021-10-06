<?php
/**
 * Basic abstract test class.
 *
 * All ClassicPress unit tests should inherit from this class.
 */
<<<<<<< HEAD

class WP_UnitTestCase extends WP_UnitTestCase_Base {
	use AssertAttributeHelper;
	use AssertClosedResource;
	use AssertEqualsSpecializations;
	use AssertFileDirectory;
	use AssertFileEqualsSpecializations;
	use AssertionRenames;
	use AssertIsType;
	use AssertNumericType;
	use AssertObjectEquals;
	use AssertStringContains;
	use EqualToSpecializations;
	use ExpectException;
	use ExpectExceptionMessageMatches;
	use ExpectExceptionObject;
	use ExpectPHPException;
}
=======
abstract class WP_UnitTestCase extends WP_UnitTestCase_Base {}
>>>>>>> 31a6cd2f78 (Build/Test Tools: Change the inheritance order of the abstract test classes.)
