<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

$mutationCollection = new MutationCollection(
    // The csp extension is required for images in the PreviewRenderer when active
    new Mutation(
        MutationMode::Extend,
        Directive::ImgSrc,
        SourceScheme::data,
        new UriValue('images.podigee-cdn.net'),
    ),
    // The csp extension is required for the IFrame in the info window
    new Mutation(
        MutationMode::Extend,
        Directive::ConnectSrc,
        SourceScheme::data,
        new UriValue('*.podigee-cdn.net'),
    ),
    new Mutation(
        MutationMode::Extend,
        Directive::FontSrc,
        SourceScheme::data,
        new UriValue('*.podigee-cdn.net'),
    ),
    new Mutation(
        MutationMode::Extend,
        Directive::FrameSrc,
        SourceScheme::data,
        new UriValue('*.podigee.io'),
    ),
    new Mutation(
        MutationMode::Extend,
        Directive::FrameSrc,
        SourceScheme::data,
        new UriValue('player.podigee-cdn.net'),
    ),
    new Mutation(
        MutationMode::Extend,
        Directive::ScriptSrc,
        SourceScheme::data,
        new UriValue('player.podigee-cdn.net'),
    ),
    new Mutation(
        MutationMode::Extend,
        Directive::StyleSrcElem,
        SourceScheme::data,
        new UriValue('player.podigee-cdn.net'),
    ),
);

return Map::fromEntries(
    [
        Scope::frontend(),
        $mutationCollection
    ],
    [
        Scope::backend(),
        $mutationCollection
    ]
);
