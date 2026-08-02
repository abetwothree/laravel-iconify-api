import { readFile } from "node:fs/promises";
import { spawnSync } from "node:child_process";
import { iconToSVG } from "@iconify/utils/lib/svg/build";
import { clearIDCache, replaceIDs } from "@iconify/utils/lib/svg/id";

const fixturePath = new URL(
    "../Fixtures/IconifyParity/vectors.json",
    import.meta.url,
);
const fixtureRaw = await readFile(fixturePath, "utf8");
const vectors = JSON.parse(fixtureRaw);

const failures = [];
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

for (const vector of vectors.build) {
    checks++;

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

for (const vector of vectors.ids) {
    checks++;

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

console.log(`Iconify parity check passed (${checks} vectors)`);
