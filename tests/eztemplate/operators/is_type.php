<?php

if ( !class_exists( 'testclass1' ) )
{
    class TestClass1
    {
    }
}

if ( !class_exists( 'testclass2' ) )
{
    class TestClass2
    {
    }
}

$object1 = new TestClass1();
$object2 = new TestClass2();

$tpl->setVariable( 'object1', $object1 );
$tpl->setVariable( 'object2', $object2 );

?>