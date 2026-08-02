import { readFile } from "node:fs/promises";
import { spawnSync } from "node:child_process";
import { getIconData } from "@iconify/utils/lib/icon-set/get-icon";
import { iconToSVG } from "@iconify/utils/lib/svg/build";
import { clearIDCache, replaceIDs } from "@iconify/utils/lib/svg/id";

const fixturePath = new URL(
    "../Fixtures/IconifyParity/vectors.json",
    import.meta.url,
);
const fixtureRaw = await readFile(fixturePath, "utf8");
const vectors = JSON.parse(fixtureRaw);

/**
 * How many vectors each section is expected to contribute.
 *
 * This is the harness's floor. Without it a renamed or emptied fixture section
 * silently drops its checks and the run still exits 0 — which would let a live
 * parity divergence through the one gate that catches it. Bump these numbers in
 * the same commit that adds or removes a vector.
 */
const expectedCounts = {
    build: 8,
    ids: 2,
    iconData: 36,
};

/** Read a fixture section, failing closed when it is missing or not a list. */
function section(name) {
    const value = vectors[name];

    if (!Array.isArray(value)) {
        throw new Error(
            `Fixture section "${name}" is missing or is not an array. ` +
                `Sections present: ${Object.keys(vectors).join(", ") || "none"}.`,
        );
    }

    return value;
}

const failures = [];
const counts = { build: 0, ids: 0, iconData: 0 };
let checks = 0;

function normalizeAttributes(attributes) {
    const keys = Object.keys(attributes).sort();
    const normalized = {};
    for (const key of keys) {
        normalized[key] = attributes[key];
    }
    return normalized;
}

function callPhp(payload) {
    const result = spawnSync("php", ["tests/bin/iconify-parity-bridge.php"], {
        input: JSON.stringify(payload),
        encoding: "utf8",
    });

    if (result.status !== 0) {
        throw new Error(result.stderr || "PHP bridge failed");
    }

    return JSON.parse(result.stdout);
}

for (const vector of section("build")) {
    checks++;
    counts.build++;

    const expected = iconToSVG(vector.icon, vector.customisations);
    const actual = callPhp({
        mode: "build",
        icon: vector.icon,
        iconSetInfo: vector.iconSetInfo,
        customisations: vector.customisations,
    });

    const expectedNormalized = {
        attributes: normalizeAttributes(expected.attributes),
        viewBox: expected.viewBox,
        body: expected.body,
    };

    const actualNormalized = {
        attributes: normalizeAttributes(actual.attributes),
        viewBox: actual.viewBox,
        body: actual.body,
    };

    if (
        JSON.stringify(expectedNormalized) !== JSON.stringify(actualNormalized)
    ) {
        failures.push({
            type: "build",
            name: vector.name,
            expected: expectedNormalized,
            actual: actualNormalized,
        });
    }
}

for (const vector of section("ids")) {
    checks++;
    counts.ids++;

    clearIDCache();
    const expectedOutputs = [];
    for (let i = 0; i < vector.times; i++) {
        expectedOutputs.push(replaceIDs(vector.body));
    }

    const actual = callPhp({
        mode: "ids",
        body: vector.body,
        times: vector.times,
    });

    if (JSON.stringify(expectedOutputs) !== JSON.stringify(actual.outputs)) {
        failures.push({
            type: "ids",
            name: vector.name,
            expected: expectedOutputs,
            actual: actual.outputs,
        });
    }
}

for (const vector of section("iconData")) {
    checks++;
    counts.iconData++;

    const resolved = getIconData(vector.iconSet, vector.name);
    const expected =
        resolved === null
            ? null
            : (() => {
                  const built = iconToSVG(resolved, vector.customisations ?? {});
                  return {
                      attributes: normalizeAttributes(built.attributes),
                      viewBox: built.viewBox,
                      body: built.body,
                  };
              })();

    const raw = callPhp({
        mode: "icon-data",
        iconSet: vector.iconSet,
        name: vector.name,
        customisations: vector.customisations ?? {},
    });

    const actual =
        raw === null
            ? null
            : {
                  attributes: normalizeAttributes(raw.attributes),
                  viewBox: raw.viewBox,
                  body: raw.body,
              };

    if (JSON.stringify(expected) !== JSON.stringify(actual)) {
        failures.push({
            type: "iconData",
            name: vector.name,
            expected,
            actual,
        });
    }
}

if (failures.length > 0) {
    console.error(
        `Iconify parity check failed (${failures.length}/${checks} mismatches)`,
    );
    for (const failure of failures) {
        console.error(`\n[${failure.type}] ${failure.name}`);
        console.error("Expected:", JSON.stringify(failure.expected));
        console.error("Actual:  ", JSON.stringify(failure.actual));
    }
    process.exit(1);
}

const shortfalls = Object.entries(expectedCounts).filter(
    ([name, expected]) => counts[name] !== expected,
);

if (shortfalls.length > 0) {
    console.error("Iconify parity check ran the wrong number of vectors.");
    for (const [name, expected] of shortfalls) {
        console.error(`  ${name}: expected ${expected}, ran ${counts[name]}`);
    }
    console.error(
        "Update expectedCounts in tests/bin/iconify-parity-check.mjs when the fixture changes.",
    );
    process.exit(1);
}

console.log(`Iconify parity check passed (${checks} vectors)`);
