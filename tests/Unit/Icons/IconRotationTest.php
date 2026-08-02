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

it('does not apply the customisation string grammar to icon data', function () {
    // Upstream adds the raw value, so '90deg' + 0 is '90deg0', whose % 4 is NaN.
    expect(IconRotation::mergeIconData('90deg', null))->toBeNull();
    expect(IconRotation::mergeIconData('25%', null))->toBeNull();
    expect(IconRotation::mergeIconData('bad-rotate', null))->toBeNull();
    expect(IconRotation::mergeIconData([], null))->toBeNull();
    expect(IconRotation::mergeIconData(null, null))->toBeNull();
});

it('merges a numeric icon data rotation without collapsing a fraction', function () {
    expect(IconRotation::mergeIconData(1, null))->toBe(1);
    expect(IconRotation::mergeIconData(1.5, null))->toBe(1.5);
    expect(IconRotation::mergeIconData(0.5, 0.5))->toBe(1.0);
    expect(IconRotation::mergeIconData(1, 3))->toBeNull();
    expect(IconRotation::mergeIconData(7, null))->toBe(3);
    expect(IconRotation::mergeIconData(-1, null))->toBe(-1);
});

it('concatenates a string icon data rotation the way JavaScript does', function () {
    // `'1' + 0` is the string '10', whose % 4 is 2 — a half turn, not a quarter one.
    expect(IconRotation::mergeIconData('1', null))->toBe(2);
    expect(IconRotation::mergeIconData('3', null))->toBe(2);
    // `'2' + 0` is '20', whose % 4 is 0, so upstream applies no rotation at all.
    expect(IconRotation::mergeIconData('2', null))->toBeNull();
    expect(IconRotation::mergeIconData('0', null))->toBeNull();
    expect(IconRotation::mergeIconData('1.5', null))->toBe(1.5);
    expect(IconRotation::mergeIconData('-1', null))->toBe(-2);
    // Which side the string sits on changes the result: `0 + '1'` is '01', i.e. 1.
    expect(IconRotation::mergeIconData(null, '1'))->toBe(1);
    expect(IconRotation::mergeIconData(null, '2'))->toBe(2);
    // Two strings concatenate with each other: `'1' + '2'` is '12', whose % 4 is 0.
    expect(IconRotation::mergeIconData('1', '2'))->toBeNull();
    expect(IconRotation::mergeIconData('1', 1))->toBe(3);
    expect(IconRotation::mergeIconData(1, '1'))->toBe(3);
});

it('applies JavaScript falsiness to an icon data rotation operand', function () {
    expect(IconRotation::mergeIconData('', null))->toBeNull();
    expect(IconRotation::mergeIconData(false, null))->toBeNull();
    expect(IconRotation::mergeIconData(0.0, null))->toBeNull();
    expect(IconRotation::mergeIconData(NAN, null))->toBeNull();
    // `true` is truthy and numifies to 1, so upstream does rotate for it.
    expect(IconRotation::mergeIconData(true, null))->toBe(1);
    expect(IconRotation::mergeIconData(null, true))->toBe(1);
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
