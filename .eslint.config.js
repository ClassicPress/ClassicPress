module.exports = [
	{
		"languageOptions": {
			"ecmaVersion": 6
		},
		"rules": {
			"block-scoped-var": "error",
			"comma-dangle": "error",
			"comma-style": [
				"error", "last"
			],
			"eol-last": [
				"error",
				"always"
			],
			"func-style": [
				"error",
				"declaration",
				{
					"allowArrowFunctions": false
				}
			],
			"linebreak-style": [
				"error",
				"unix"
			],
			"no-confusing-arrow": "error",
			"no-cond-assign": "off",
			"no-empty": "off",
			"no-eval": "error",
			"no-extra-boolean-cast": "off",
			"no-implied-eval": "error",
			"no-misleading-character-class": "off",
			"no-mixed-spaces-and-tabs": "off",
			"no-prototype-builtins": "off",
			"no-redeclare": "off",
			"no-shadow-restricted-names": "off",
			"no-trailing-spaces": "error",
			"no-undef": "off",
			"no-unused-vars": "off",
			"no-useless-escape": "off",
			"semi": [
				"error",
				"always"
			],
			"semi-spacing": "error"
		}
	}
];
