# psalm-strict-types
A [Psalm](https://github.com/vimeo/psalm) plugin to add declaration of stricts_types by analyzing content of a file

Installation:

```console
$ composer require --dev orklah/psalm-strict-types
$ vendor/bin/psalm-plugin enable orklah/psalm-strict-types
```

Usage:

To launch standard analysis, run your usual Psalm command:
```console
$ vendor/bin/psalm
```
To automatically add strict_types declarations
```console
$ vendor/bin/psalm --alter --plugin=vendor/orklah/psalm-strict-types/src/Plugin.php
```

Explanation:

Warning: While this plugin has been designed with safety in mind, analyzing code is hard. This plugin may add strict_types declaration on files that could broke your production environment. Please use carefully

This plugin uses Psalm type inference and PHP-Parser's node parsing to check every possible strict_types violation:
- Parameters in methods/functions calls
- Return statement for methods/functions
- Properties assignation

When it encounters a potentially problematic code, it will create a Psalm issue based on the criticity of the code:

- BadTypeFromDocblockIssue
- MixedBadTypeFromDocblockIssue
- PartialBadTypeFromDocblockIssue

These issues are emitted when encountering a type (inferred from docblock) that is not expected on a file that is not yet strict. The given type can either be mixed (generally not enough documented), partial (only part of an Union is expected) or totally bad (a completely different type)

- BadTypeFromDocblockOnStrictFileIssue
- MixedBadTypeFromDocblockOnStrictFileIssue
- PartialBadTypeFromDocblockOnStrictFileIssue

These issues are emitted when encountering a type (inferred from docblock) that is not expected on a file that is already strict. The given type can either be mixed (generally not enough documented), partial (only part of an Union is expected) or totally bad (a completely different type)

- BadTypeFromSignatureIssue
- MixedBadTypeFromSignatureIssue
- PartialBadTypeFromSignatureIssue

These issues are emitted when encountering a type (inferred from signature) that is not expected on a file that is not yet strict. The given type can either be mixed (generally not enough documented), partial (only part of an Union is expected) or totally bad (a completely different type)

- BadTypeFromSignatureOnStrictFileIssue
- MixedBadTypeFromSignatureOnStrictFileIssue
- PartialBadTypeFromSignatureOnStrictFileIssue

These issues are emitted when encountering a type (inferred from signature) that is not expected on a file that is already strict. The given type can either be mixed (generally not enough documented), partial (only part of an Union is expected) or totally bad (a completely different type)

- GoodTypeFromDocblockIssue

This issue is emitted when encountering a type that is expected but inferred from docblock. This plugin will not automatically add a strict_types declaration in this case because the docblock *may* be wrong

- StrictDeclarationToAddIssue

When not in --alter mode, this issue will be emitted when the plugin detects that a declaration can be added safely for this file

Notes:

The philosophy of this plugin does not match Psalm's. In effect, solving every issue from this plugin will probably create new issues in Psalm core. (for example RedundantCast)
This is due to the decision of not trusting docblock on this tool to avoid adding strict_types when docblock is wrong.
