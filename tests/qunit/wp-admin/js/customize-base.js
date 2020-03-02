/* global wp, test, ok, equal, module */

jQuery( function( $ ) {
	var FooSuperClass, BarSubClass, foo, bar, ConstructorTestClass, newConstructor, constructorTest, $mockElement, mockString,
	firstInitialValue, firstValueInstance, valuesInstance, wasCallbackFired, mockValueCallback;

	module( 'Customize Base: Class' );

	FooSuperClass = wp.customize.Class.extend(
		{
			initialize: function ( instanceProps ) {
				$.extend( this, instanceProps || {} );
			},
			protoProp: 'protoPropValue'
		},
		{
			staticProp: 'staticPropValue'
		}
	);
	test( 'FooSuperClass is a function ', function () {
		equal( typeof FooSuperClass, 'function' );
	});
	test( 'FooSuperClass prototype has protoProp', function () {
		equal( FooSuperClass.prototype.protoProp, 'protoPropValue' );
	});
	test( 'FooSuperClass does not have protoProp', function () {
		equal( typeof FooSuperClass.protoProp, 'undefined' );
	});
	test( 'FooSuperClass has staticProp', function () {
		equal( FooSuperClass.staticProp, 'staticPropValue' );
	});
	test( 'FooSuperClass prototype does not have staticProp', function () {
		equal( typeof FooSuperClass.prototype.staticProp, 'undefined' );
	});

	foo = new FooSuperClass( { instanceProp: 'instancePropValue' } );
	test( 'FooSuperClass instance foo extended Class', function () {
		equal( foo.extended( wp.customize.Class ), true );
	});
	test( 'foo instance has protoProp', function () {
		equal( foo.protoProp, 'protoPropValue' );
	});
	test( 'foo instance does not have staticProp', function () {
		equal( typeof foo.staticProp, 'undefined' );
	});
	test( 'FooSuperClass instance foo ran initialize() and has supplied instanceProp', function () {
		equal( foo.instanceProp, 'instancePropValue' );
	});

	// @todo Test Class.applicator?
	// @todo do we test object.instance?

	module( 'Customize Base: Subclass' );

	BarSubClass = FooSuperClass.extend(
		{
			initialize: function ( instanceProps ) {
				FooSuperClass.prototype.initialize.call( this, instanceProps );
				this.subInstanceProp = 'subInstancePropValue';
			},
			subProtoProp: 'subProtoPropValue'
		},
		{
			subStaticProp: 'subStaticPropValue'
		}
	);
	test( 'BarSubClass prototype has subProtoProp', function () {
		equal( BarSubClass.prototype.subProtoProp, 'subProtoPropValue' );
	});
	test( 'BarSubClass prototype has parent FooSuperClass protoProp', function () {
		equal( BarSubClass.prototype.protoProp, 'protoPropValue' );
	});

	bar = new BarSubClass( { instanceProp: 'instancePropValue' } );
	test( 'BarSubClass instance bar its initialize() and parent initialize() run', function () {
		equal( bar.instanceProp, 'instancePropValue' );
		equal( bar.subInstanceProp, 'subInstancePropValue' );
	});

	test( 'BarSubClass instance bar extended FooSuperClass', function () {
		equal( bar.extended( FooSuperClass ), true );
	});


	// Implements todo : Test Class.constructor() manipulation
	module( 'Customize Base: Constructor Manipulation' );

	newConstructor = function ( instanceProps ) {
			$.extend( this , instanceProps || {} );
	};

	ConstructorTestClass = wp.customize.Class.extend(
		{
			constructor : newConstructor,
			protoProp: 'protoPropValue'
		},
		{
			staticProp: 'staticPropValue'
		}
	);

	test( 'New constructor added to class' , function () {
		equal( ConstructorTestClass.prototype.constructor , newConstructor );
	});
	test( 'Class with new constructor has protoPropValue' , function () {
		equal( ConstructorTestClass.prototype.protoProp , 'protoPropValue' );
	});

	constructorTest = new ConstructorTestClass( { instanceProp: 'instancePropValue' } );
		test( 'ConstructorTestClass instance constructorTest has the new constructor', function () {
		equal( constructorTest.constructor, newConstructor );
	});

	test( 'ConstructorTestClass instance constructorTest extended Class', function () {
		equal( constructorTest.extended( wp.customize.Class ), true );
	});

	test( 'ConstructorTestClass instance constructorTest has the added instance property', function () {
		equal( constructorTest.instanceProp , 'instancePropValue' );
	});


	module( 'Customize Base: wp.customizer.ensure' );

	$mockElement = $( '<div id="mockElement"></div>' );

	test( 'Handles jQuery argument' , function() {
		equal( wp.customize.ensure( $mockElement ) , $mockElement );
	});

	mockString = '<div class="mockString"></div>';

	test( 'Handles string argument' , function() {
		ok( wp.customize.ensure( mockString ) instanceof jQuery );
	});


	module( 'Customize Base: Value Class' );

	firstInitialValue = true;
	firstValueInstance = new wp.customize.Value( firstInitialValue );

	test( 'Initialized with the right value' , function() {
		equal( firstValueInstance.get() , firstInitialValue );
	});

	test( '.set() works' , function() {
		firstValueInstance.set( false );
		equal( firstValueInstance.get() , false );
	});

	test( '.bind() adds new callback that fires on set()' , function() {
		wasCallbackFired = false;
		mockValueCallback = function() {
			wasCallbackFired = true;
		};
		firstValueInstance.bind( mockValueCallback );
		firstValueInstance.set( 'newValue' );
		ok( wasCallbackFired );
	});

	module( 'Customize Base: Values Class' );

	valuesInstance = new wp.customize.Values();

	test( 'Correct events are triggered when adding to or removing from Values collection', function() {
		var hasFooOnAdd = false,
			hasFooOnRemove = false,
			hasFooOnRemoved = true,
			valuePassedToAdd = false,
			valuePassedToRemove = false,
			valuePassedToRemoved = false,
			wasEventFiredOnRemoval = false,
			fooValue = new wp.customize.Value( 'foo' );

		// Test events when adding new value.
		valuesInstance.bind( 'add', function( value ) {
			hasFooOnAdd = valuesInstance.has( 'foo' );
			valuePassedToAdd = value;
		} );
		valuesInstance.add( 'foo', fooValue );
		ok( hasFooOnAdd );
		equal( valuePassedToAdd.get(), fooValue.get() );

		// Test events when removing the value.
		valuesInstance.bind( 'remove', function( value ) {
			hasFooOnRemove = valuesInstance.has( 'foo' );
			valuePassedToRemove = value;
			wasEventFiredOnRemoval = true;
		} );
		valuesInstance.bind( 'removed', function( value ) {
			hasFooOnRemoved = valuesInstance.has( 'foo' );
			valuePassedToRemoved = value;
			wasEventFiredOnRemoval = true;
		} );
		valuesInstance.remove( 'foo' );
		ok( hasFooOnRemove );
		equal( valuePassedToRemove.get(), fooValue.get() );
		ok( ! hasFooOnRemoved );
		equal( valuePassedToRemoved.get(), fooValue.get() );

		// Confirm no events are fired when nonexistent value is removed.
		wasEventFiredOnRemoval = false;
		valuesInstance.remove( 'bar' );
		ok( ! wasEventFiredOnRemoval );
	});

	module( 'Customize Base: Notification' );
	test( 'Notification object exists and has expected properties', function ( assert ) {
		var notification = new wp.customize.Notification( 'mycode', {
			'message': 'Hello World',
			'type': 'update',
			'setting': 'blogname',
			'fromServer': true,
			'data': { 'foo': 'bar' }
		} );

		assert.equal( 'mycode', notification.code );
		assert.equal( 'Hello World', notification.message );
		assert.equal( 'update', notification.type );
		assert.equal( 'blogname', notification.setting );
		assert.equal( true, notification.fromServer );
		assert.deepEqual( { 'foo': 'bar' }, notification.data );

		notification = new wp.customize.Notification( 'mycode2', {
			'message': 'Hello Space'
		} );
		assert.equal( 'mycode2', notification.code );
		assert.equal( 'Hello Space', notification.message );
		assert.equal( 'error', notification.type );
		assert.equal( null, notification.data );
	} );

	module( 'Customize Base: utils.parseQueryString' );
	test( 'wp.customize.utils.parseQueryString works', function( assert ) {
		var queryParams;
		queryParams = wp.customize.utils.parseQueryString( 'a=1&b=2' );
		assert.ok( _.isEqual( queryParams, { a: '1', b: '2' } ) );

		queryParams = wp.customize.utils.parseQueryString( 'a+b=1&b=Hello%20World' );
		assert.ok( _.isEqual( queryParams, { 'a_b': '1', b: 'Hello World' } ) );

		queryParams = wp.customize.utils.parseQueryString( 'a%20b=1&b=Hello+World' );
		assert.ok( _.isEqual( queryParams, { 'a_b': '1', b: 'Hello World' } ) );

		queryParams = wp.customize.utils.parseQueryString( 'a=1&b' );
		assert.ok( _.isEqual( queryParams, { 'a': '1', b: null } ) );

		queryParams = wp.customize.utils.parseQueryString( 'a=1&b=' );
		assert.ok( _.isEqual( queryParams, { 'a': '1', b: '' } ) );
	} );
});
