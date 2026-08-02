<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconRotation;

it('parses a rotation string the way rotateFromString does', function () {
    expect(IconRotation::parse('2'))->toBe(2);
    expect(IconRotation::parse('-1'))->toBe(3);
    expect(IconRotation::parse('180deg'))->toBe(2);
    expect(IconRotation::parse('50%'))->toBe(2);
    expect(IconRotation::parse('75%'))->toBe(3);
    expect(IconRotation::parse('45deg'))->toBe(0);
    expect(IconRotation::parse('10rad'))->toBe(0);
    expect(IconRotation::parse('12rad'))->toBe(0);
    expect(IconRotation::parse('.'))->toBe(0);
    expect(IconRotation::parse('.deg'))->toBe(0);
    expect(IconRotation::parse('deg'))->toBe(0);
    expect(IconRotation::parse(''))->toBe(0);
    expect(IconRotation::parse('bad-rotate'))->toBe(0);
});

it('hands a numeric rotation over untouched', function () {
    expect(IconRotation::parse(3))->toBe(3);
    expect(IconRotation::parse(7))->toBe(7);
    expect(IconRotation::parse(2.9))->toBe(2.9);
    expect(IconRotation::parse(-1.6))->toBe(-1.6);
});

it('treats a rotation it cannot read as no rotation', function () {
    expect(IconRotation::parse([]))->toBe(0);
    expect(IconRotation::parse(null))->toBe(0);
    expect(IconRotation::parse(true))->toBe(0);
});

it('reduces a rotation to the switch case iconToSVG would take', function () {
    expect(IconRotation::normalise(0))->toBe(0);
    expect(IconRotation::normalise(7))->toBe(3);
    expect(IconRotation::normalise(-5))->toBe(3);
    expect(IconRotation::normalise(-4))->toBe(0);
    expect(IconRotation::normalise(2.0))->toBe(2);
    expect(IconRotation::normalise(-1.0))->toBe(3);
    expect(IconRotation::normalise(0.5 + 0.5))->toBe(1);
});

it('applies no rotation for a value that matches no switch case', function () {
    expect(IconRotation::normalise(1.5))->toBe(0);
    expect(IconRotation::normalise(-1.5))->toBe(0);
    expect(IconRotation::normalise(3.7))->toBe(0);
    // 1.5 plus the 2 a double flip adds is still fractional.
    expect(IconRotation::normalise(3.5))->toBe(0);
    expect(IconRotation::normalise(INF))->toBe(0);
    expect(IconRotation::normalise(NAN))->toBe(0);
});

it('keeps the sign and the fraction when reducing modulo four', function () {
    expect(IconRotation::modulo(5))->toBe(1);
    expect(IconRotation::modulo(-1))->toBe(-1);
    expect(IconRotation::modulo(5.5))->toBe(1.5);
    expect(IconRotation::modulo(-5.5))->toBe(-1.5);
});
