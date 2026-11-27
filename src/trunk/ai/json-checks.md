# Checks of the form editor's JSON linter

Generated from survey-creator-core 3.2.4 (the English messages and severities of Survey Creator's JSON Editor) and the SurveyJS linter of survey-core 3.2.4 by `scripts/update-json-checks.js`. Do not hand-edit: the next run overwrites this file.

The form editor checks the survey JSON with the SurveyJS linter: the JSON Editor tab of Survey Creator with SurveyJS Forms Add-on, the Static analysis panel of the Config tab without the add-on. Every finding is an issue in "Form saved with N issues". Write JSON that passes every check below. When the person asks to fix the issues, change the JSON so that none of these messages applies any more. Words in braces are placeholders filled in from the JSON. A rule marked off does not run in that editor.

## expression/syntax (error)

- The expression "{expression}" cannot be parsed.

## reference/unknown (error)

- "{name}" is not found - no question, panel, page, calculated value, or variable with that name exists.
- "{segment}" is not found in {containerType} "{root}" (reference: {name}).
- "{segment}" is not found in the "{scopePrefix}" scope (reference: {name}).
- The keyName of "{name}" names "{key}" - "{name}" has no {keyNoun} with that name, so duplicate-key validation never runs.
- "{name}" is not found.

## reference/self (error)

- The {prop} of "{name}" references the element itself (reference: {reference}).

## name/duplicate (error)

- The name "{name}" is duplicated.
- The calculated value name "{name}" is already used by another calculated value.
- The calculated value "{name}" shares its name with another element, so one of them shadows the other.

## name/shadowing (warning)

- The {nameKindText} "{name}" of this {ownerText} is also the built-in survey variable {{builtIn}} - the survey answers {{name}} first, so this one is unreachable in expressions.
- The valueName "{valueName}" of "{name}" shares the value of {otherType} "{otherName}", which has no valueName of its own - "{otherName}" treats the fields "{name}" adds as unknown, and clearIncorrectValues() deletes them. Set its valueName to "{valueName}" too.
- The row "{key}" of matrixdropdown "{otherName}" is also {sharedKeyText} "{name}" sharing its value - a cell object and a single value cannot both live under one key; the runtime keeps one and deletes the other.
- The {type} "{name}" shares the data key "{dataKey}" with {otherType} "{otherName}" - it keeps {shapeText} there, while "{otherName}" keeps {otherShapeText}; one cannot hold the other's value, so clearIncorrectValues() deletes it.
- The data key "{dataName}" is also the comment key of "{base}" (its data key plus "{suffix}") - one write silently overwrites the other.
- The data key "{dataName}" is also the totals key of "{base}" (its data key plus "{suffix}") - one write silently overwrites the other.
- The {trigger} trigger sets the variable "{name}", which is also the data key of question "{questionName}" - the variable answers {{name}} from then on, not the question.

## name/reserved (error)

- The name "{name}" is reserved - a member of Object.prototype.
- The valueName "{valueName}" of "{name}" is reserved - a member of Object.prototype.
- The column "{name}" of "{matrixName}" is reserved - a member of Object.prototype.
- The item "{name}" of "{questionName}" is reserved - a member of Object.prototype.
- The row "{rowValue}" of "{name}" is reserved - a member of Object.prototype.
- The calculated value "{name}" is reserved - a member of Object.prototype.

## variable/collision (error)

- The variable definition declares "{variable}", which is also the data key of question "{name}" - setting the variable deletes the answer stored under that key, and {{name}} answers the host value from then on.
- The variable definition declares "{variable}", which is also the name of calculated value "{name}" - both write the same slot, and whichever runs last wins.

## variable/preset (off in Survey Creator, warning on the Config tab without the add-on)

- variablePresets.definition is not a survey JSON object, so no variable is declared and no preset value can be checked.
- variablePresets.presets is not an array, so no preset is declared.
- Preset #{index} is not an object.
- Preset #{index} has no name, so nothing can reference it.
- Preset "{preset}" carries no variables object, so it sets nothing.
- Preset "{preset}" is declared twice - a lookup by that name answers with the first one.
- Preset "{preset}" sets "{variable}", which the variable definition does not declare.
- Preset "{preset}" sets "{variable}" to a value the variable definition rejects: {errors}

## element/unknown-type (error in Survey Creator, info on the Config tab without the add-on)

- "{name}" has an unknown type "{type}".
- "{name}" has no type - an element without a type is dropped.

## property/unknown (error in Survey Creator, warning on the Config tab without the add-on)

- "{key}" is not a property of {ownerText} ({className}).

## property/dead (info)

- "{key}" of {ownerText} is not serializable - it takes effect on load, and is dropped from the JSON whenever the survey is saved again.
- "{key}" and "{aliasKey}" of {ownerText} are two names of one property - the run time applies them in the order the JSON writes them, so "{winner}" wins.
- "{key}" is set on "{name}", but inputType "{inputType}" has no bounds - the run time ignores it.

## property/invalid-value (error in Survey Creator, warning on the Config tab without the add-on)

- The {key} of {ownerText} is {valueText} - not one of the allowed values ({allowedText}).
- The {key} of {ownerText} is {value}, outside its allowed range {rangeText}.
- The valueName "{valueName}" of "{name}" contains a "." - expressions read {{valueName}} as a path into "{rootKey}", so the data key itself can never be addressed.

## property/not-an-array (error in Survey Creator, warning on the Config tab without the add-on)

- The "{key}" of {ownerText} is not an array - the property holds a list, and the run time wraps the value into a one-item array.

## property/required (error)

- {ownerText} has no "{key}" - the property is required for a {className}.
- The name of the {className} is {valueText}, not a string - the survey cannot load it.

## expression/unknown-function (warning)

- The function "{functionName}" is not registered.

## cycle/calculated-value (error)

- The calculated value "{names}" references itself in its own expression.
- Calculated values {names} depend on each other.

## cycle/trigger (warning)

- The trigger reacts to the value it sets itself ("{setToName}").
- Triggers form a loop through the values they set: {setRoots}.

## cycle/value-write (warning)

- The {label} reads the value it writes itself - it runs only when another value changes, so it never runs at all.
- Values are written in a loop: {chain}. Each write reruns the expressions that read it, so the final values depend on the order the questions are answered in.

## expression/unknown-choice (warning)

- The condition compares "{name}" to {values} - not among its choices. Available: {available}.
- The condition compares "{name}" to {values} - no choice value contains it. Available: {available}.

## expression/type-mismatch (warning)

- The condition applies "{operator}" to "{name}": "{recordName}" ({questionType}) has no value to compare.
- The condition applies "{operator}" to "{name}": "{recordName}" holds {valueShapeText} - ordering and arithmetic operators do not apply to it.
- The condition applies "{operator}" to "{name}": "{recordName}" is a boolean question - ordering operators do not apply to it.
- The condition applies "{operator}" to "{name}": "{recordName}" is a text question - its value is a string, so numeric comparison relies on implicit conversion.
- The condition applies "{operator}" to "{name}": "{recordName}" holds a date string - comparing it to the number {constValue} cannot hold.
- The condition applies "{operator}" to "{name}": "{recordName}" is numeric - comparing it to the string "{constValue}" cannot hold.
- The condition applies "{operator}" to "{name}": "{recordName}" holds an array of selected values, so "=" compares the whole array.
- The condition applies "{operator}" to "{name}": "{recordName}" is a boolean question - comparing it to {constValue} cannot hold.

## expression/contradiction (warning)

- The {prop} "{expression}" is always false, so "{name}" is never shown.
- The {prop} "{expression}" never holds, because {facts}.
- The {prop} "{expression}" never holds - no allowed value satisfies it: {facts}.
- The {prop} "{expression}" contradicts itself: {facts}.

## expression/meaningless-condition (warning)

- The {prop} "{expression}" is always true, so it decides nothing.
- The {prop} "{expression}" is arithmetic, not a comparison, so it never gives a yes or no.
- Part of the {prop} "{expression}" has a result that is known upfront.
- The {prop} "{expression}" always holds, because {facts} - it decides nothing.

## expression/no-reference (off)

- The {prop} "{expression}" does not read any question or variable.

## choices/dead-source (error)

- "{name}" copies its choices from "{source}", but no question with that name exists.
- "{name}" copies its choices from itself.
- "{name}" copies its choices from "{source}" ({sourceType}), which provides neither choices nor an array of values.
- "{name}" reads {prop} "{field}" from "{source}", but {sourceType} "{source}" has no such {fieldNoun}.

## choices/duplicate (warning)

- Another item of the {prop} of "{name}" already has the value {valueText} - the run time keeps both items.
- The choices of "{name}" contain {valueText} while {toggleProp} is on - it collides with the built-in {specialItemText} item.

## trigger/unknown-target (error)

- The {trigger} trigger targets page "{name}", which does not exist.
- The {trigger} trigger targets "{name}", but {containerType} "{root}" has no {segmentNoun} "{segment}".
- The {trigger} trigger {verb} "{name}", but no {kindText} with that name exists.

## trigger/unknown-type (error in Survey Creator, warning on the Config tab without the add-on)

- The trigger type "{type}" is not known.
- The trigger has no type.

## validator/unknown-type (error in Survey Creator, warning on the Config tab without the add-on)

- The validator type "{type}" of "{name}" is not known.
- A validator of "{name}" has no type.

## validator/dead (warning)

- The {validatorType} validator of "{name}" {effectText}: {causeText} ({questionType}).
- The {validatorType} validator of "{name}" requires at least {min} and at most {max} - no answer satisfies it.
- The answercount validator of "{name}" requires at least {minCount} answers, above the {selectable} choices that can be selected together.
- The regex validator of "{name}" has a pattern the engine rejects: {error}.
- The expression validator of "{name}" has no expression, so it always passes.

## value/not-a-choice (warning)

- The default value of "{name}" is {valuesText}, which it can never hold. Allowed: {availableText}.
- The correct answer of "{name}" is {valuesText}, which it can never hold. Allowed: {availableText}.
- The trigger sets "{name}" to {valuesText}, which it can never hold. Allowed: {availableText}.
- The default row value sets "{name}" to {valuesText}, which it can never hold. Allowed: {availableText}.
- The default panel value sets "{name}" to {valuesText}, which it can never hold. Allowed: {availableText}.
- The {prop} of "{name}" names "{key}" - no such row. Available: {availableText}.
- The {prop} of "{name}" names "{key}" - no such column. Available: {availableText}.
- The {prop} of "{name}" names "{key}" - no such template question. Available: {availableText}.
- The copyvalue trigger copies "{fromName}" into "{setToName}", but "{fromName}" holds {sourceShapeText} and "{setToName}" holds {targetShapeText}.
- The copyvalue trigger copies "{fromName}" into "{setToName}", but no value of "{fromName}" is among the values "{setToName}" can hold. Allowed: {availableText}.

## element/count-contradiction (warning)

- The {minProp} of "{name}" is {min}, above its {maxProp} of {max} - the run time silently adjusts one of them.
- The {countProp} of "{name}" is {count}, {direction} its {boundProp} of {bound} - the run time clamps it.
- The {stepProp} of "{name}" is {step}, but the range it steps through ({minProp}..{maxProp}) spans only {range} - the run time clamps it.
- The minSelectedChoices of "{name}" is {min}, above the {selectable} choices that can be selected together - the question can never be answered.

## element/never-visible (warning)

- "{name}" can never become visible: its visibleIf reads {reads}, which {deadClause}, so the condition never holds.

## mask/mismatch (warning)

- The maskType "{maskType}" of "{name}" is not a known mask - the run time falls back to no mask at all.
- The maskSettings of "{name}" set "{key}", which is not a property of the "{maskType}" mask - the run time drops it silently.
- The maskSettings of "{name}" are set without a maskType - the run time keeps only "saveMaskedValue" and drops the rest.
- The {maskType} mask of "{name}" applies to no input: inputType "{inputType}" is masked only for text and tel.
- The datetime mask of "{name}" sets min/max without a pattern - the bounds apply to the pattern's date parts, so without one they do nothing.
- The {maskType} mask of "{name}" allows at least {min} and at most {max} - no value satisfies it.

## page/empty (warning)

- The dynamic panel "{name}" has an empty template - its panels have nothing to render.
- The {kindText} "{name}" has no elements.
- The {kindText} "{name}" has no elements that can ever render - every element is hidden, guarded by a condition that never holds, or empty.
- The detail elements of "{name}" are never shown: its detailPanelMode is "none", which is the default.
