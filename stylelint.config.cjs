module.exports = {
  customSyntax: 'postcss-scss',
  ignoreFiles: ['**/*.css', 'node_modules/**', 'vendor/**'],
  rules: {
    'at-rule-no-unknown': [
      true,
      {
        ignoreAtRules: [
          'at-root',
          'content',
          'debug',
          'each',
          'else',
          'error',
          'extend',
          'for',
          'forward',
          'function',
          'if',
          'include',
          'mixin',
          'return',
          'use',
          'warn',
          'while'
        ]
      }
    ],
    'declaration-block-no-duplicate-custom-properties': true,
    'function-url-quotes': 'always'
  }
};
