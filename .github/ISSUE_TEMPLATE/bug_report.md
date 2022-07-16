name: Bug Report
description: Create a report to help us improve
title: "[Bug]: "
labels: ["bug", "triage"]
body:
  - type: markdown
    attributes:
      value: |
        Thanks for taking the time to fill out this bug report!
  - type: dropdown
    id: repository
    attributes:
      label: Repository
      description: Which repository did the bug occure in (main/contrib)
      options:
        - main (opentelemetry-php)
        - contrib (opentelemetry-php-contrib)
    validations:
      required: true
      
  - type: textarea
    id: environment
    attributes:
      label: Describe your environment
      description: Describe any aspect of your environment relevant to the problem, including your php version (`php -v` will tell you your current version), version numbers of installed dependencies, information about your cloud hosting provider, etc. If you're reporting a problem with a specific version of a library in this repo, please check whether the problem has been fixed on master.
    validations:
      required: false

  - type: textarea
    id: reproduce
    attributes:
      label: Steps to reproduce
      description: Describe exactly how to reproduce the error. Include a code sample if applicable.
    validations:
      required: true
  
  - type: textarea
    id: expcted
    attributes:
      label: What is the expected behavior?
      description: What did you expect to see?
    validations:
      required: true
      
  - type: textarea
    id: actual
    attributes:
      label: What is the actual behavior?
      description: What did you see instead?
    validations:
      required: true
      
  - type: textarea
    id: context
    attributes:
      label: Additional context
      description: Add any other context about the problem here.
    validations:
      required: false
