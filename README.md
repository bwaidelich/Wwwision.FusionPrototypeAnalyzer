# Wwwision.FusionPrototypeAnalyzer

Analyze Neos Fusion prototypes and their usages by other prototypes.

## Installation

Install via composer:

    composer require --dev wwwision/fusion-prototype-analyzer

## Usage

This package comes with two CLI commands:

### Find nested prototypes

Allows finding prototypes that are used by the specified prototype (recursively):

    ./flow prototype:findnested Some.Package:Some.Prototype

This might output something like:

    The prototype Some.Package:Some.Prototype contains 6 other prototypes (for site package Some.Package):

    Neos.Fusion (4)
       Tag
       DataStructure
       Component
       Case

    Some.Package (2)
       Component.Atom.SomeComponent
       Component.Atom.SomeOtherComponent

#### Site package

In order for the right Fusion Object Tree to be loaded, the context site package key can be specified:

    ./flow prototype:findnested Some.Package:Some.Prototype --site-package Some.Other.Package

If it is omitted, the site package is extracted from the specified prototype name


### Find prototype usages

Allows finding prototypes that use the specified prototype (recursively):

    ./flow prototype:findusages Some.Package:Some.Prototype

This might output something like:

    The prototype Some.Package:Some.Prototype is used by 4 other prototypes (for site package Some.Package):

    Some.Package (5)
       Component.Template.Component1
       Component.Template.Component2
       Document.SomeDocument
       Document.SomeOtherDocument


#### Site package

In order for the right Fusion Object Tree to be loaded, the context site package key can be specified:

    ./flow prototype:findusages Some.Package:Some.Prototype --site-package Some.Other.Package

If it is omitted, the site package is extracted from the specified prototype name

### Find prototypes used by Node Type

Allows finding prototypes that are used by a specified Node Type (recursively):

    ./flow prototype:findbynodetype Some.Package:Some.NodeType

This might output something like:

    The node type Some.Package:Some.NodeType uses 3 Fusion prototypes (for site package Some.Package):

    Some.Package (5)
       Component.Template.Component1
       Component.Template.Component2
       Document.SomeDocument
       Document.SomeOtherDocument


#### Site package

In order for the right Fusion Object Tree to be loaded, the context site package key can be specified:

    ./flow prototype:findbynodetype Some.Package:Some.NodeType --site-package Some.Other.Package

If it is omitted, the site package is extracted from the specified node type name

## Contribution

Contributions in the form of issues or pull requests are highly appreciated.

## License

See [LICENSE](./LICENSE)
