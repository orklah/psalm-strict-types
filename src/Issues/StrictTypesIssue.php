<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Issues;

use Orklah\StrictTypes\Core\FileContext;
use Orklah\StrictTypes\Exceptions\ShouldNotHappenException;
use Orklah\StrictTypes\Issues\FromDocblock\BadTypeFromDocblockIssue;
use Orklah\StrictTypes\Issues\FromDocblock\BadTypeFromDocblockOnStrictFileIssue;
use Orklah\StrictTypes\Issues\FromDocblock\MixedBadTypeFromDocblockIssue;
use Orklah\StrictTypes\Issues\FromDocblock\MixedBadTypeFromDocblockOnStrictFileIssue;
use Orklah\StrictTypes\Issues\FromDocblock\PartialBadTypeFromDocblockIssue;
use Orklah\StrictTypes\Issues\FromDocblock\PartialBadTypeFromDocblockOnStrictFileIssue;
use Orklah\StrictTypes\Issues\FromSignature\BadTypeFromSignatureIssue;
use Orklah\StrictTypes\Issues\FromSignature\BadTypeFromSignatureOnStrictFileIssue;
use Orklah\StrictTypes\Issues\FromSignature\MixedBadTypeFromSignatureIssue;
use Orklah\StrictTypes\Issues\FromSignature\MixedBadTypeFromSignatureOnStrictFileIssue;
use Orklah\StrictTypes\Issues\FromSignature\PartialBadTypeFromSignatureIssue;
use Orklah\StrictTypes\Issues\FromSignature\PartialBadTypeFromSignatureOnStrictFileIssue;
use PhpParser\Node;
use Psalm\CodeLocation;
use Psalm\Issue\CodeIssue;
use Psalm\IssueBuffer;

// this is the parent issue and also factory
abstract class StrictTypesIssue extends CodeIssue
{
    /**
     * @throws ShouldNotHappenException
     */
    private static function createFromNode(FileContext $file_context, Node $node, string $message, bool $is_correct, bool $is_from_docblock, bool $is_partial, bool $is_mixed): self
    {
        $has_strict_declaration = $file_context->isHaveDeclareStatement();
        $statements_source = $file_context->getStatementsSource();
        if($is_correct){
            if($is_from_docblock){
                $issue = new GoodTypeFromDocblockIssue($message, new CodeLocation($statements_source, $node));
            } else {
                throw new ShouldNotHappenException('unrecognized issue');
            }
        } elseif($has_strict_declaration){
            if($is_from_docblock){
                if($is_partial){
                    $issue = new PartialBadTypeFromDocblockOnStrictFileIssue($message, new CodeLocation($statements_source, $node));
                } elseif($is_mixed) {
                    $issue = new MixedBadTypeFromDocblockOnStrictFileIssue($message, new CodeLocation($statements_source, $node));
                } else {
                    $issue = new BadTypeFromDocblockOnStrictFileIssue($message, new CodeLocation($statements_source, $node));
                }
            } else{
                if($is_partial){
                    $issue = new PartialBadTypeFromSignatureOnStrictFileIssue($message, new CodeLocation($statements_source, $node));
                } elseif($is_mixed) {
                    $issue = new MixedBadTypeFromSignatureOnStrictFileIssue($message, new CodeLocation($statements_source, $node));
                } else {
                    $issue = new BadTypeFromSignatureOnStrictFileIssue($message, new CodeLocation($statements_source, $node));
                }
            }
        } else {
            if($is_from_docblock){
                if($is_partial){
                    $issue = new PartialBadTypeFromDocblockIssue($message, new CodeLocation($statements_source, $node));
                } elseif($is_mixed) {
                    $issue = new MixedBadTypeFromDocblockIssue($message, new CodeLocation($statements_source, $node));
                } else {
                    $issue = new BadTypeFromDocblockIssue($message, new CodeLocation($statements_source, $node));
                }
            } else{
                if($is_partial){
                    $issue = new PartialBadTypeFromSignatureIssue($message, new CodeLocation($statements_source, $node));
                } elseif($is_mixed) {
                    $issue = new MixedBadTypeFromSignatureIssue($message, new CodeLocation($statements_source, $node));
                } else {
                    $issue = new BadTypeFromSignatureIssue($message, new CodeLocation($statements_source, $node));
                }
            }
        }
        return $issue;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public static function emitIssue(FileContext $file_context, Node $node, string $message, bool $is_correct, bool $is_from_docblock, bool $is_partial, bool $is_mixed): void {
        $issue = self::createFromNode($file_context, $node, $message, $is_correct, $is_from_docblock, $is_partial, $is_mixed);
        if(IssueBuffer::accepts($issue, $file_context->getStatementsSource()->getSuppressedIssues())){
            //one day, we may want to add declarations based on what the user suppressed (for example, PartialFromDocblock who is probably the badest issue)
        }
    }
}
