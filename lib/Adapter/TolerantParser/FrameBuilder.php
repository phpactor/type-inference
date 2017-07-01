<?php

namespace DTL\TypeInference\Adapter\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use DTL\TypeInference\Domain\Variable;
use DTL\TypeInference\Domain\InferredType;
use Microsoft\PhpParser\Node\Parameter;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;
use Microsoft\PhpParser\Node\Expression\Variable as ExprVariable;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\Node\SourceFileNode;

final class FrameBuilder
{
    public function buildUntil(Node $node): Frame
    {
        $frame = new Frame();
        if (null === $start = $this->getFrameStart($node)) {
            return $frame;
        }

        $this->walk($frame, $start);

        return $frame;
    }

    private function getFrameStart(Node $node)
    {
        return $node->getFirstAncestor(
            MethodDeclaration::class,
            FunctionDeclaration::class,
            SourceFileNode::class
        );
    }

    private function walk(Frame $frame, Node $node)
    {
        if ($node instanceof MethodDeclaration) {
            $this->processMethodDeclaration($frame, $node);
        }

        if ($node instanceof AssignmentExpression) {
            $frame->set(
                $node->leftOperand,
                $node->rightOperand
            );
        }

        foreach ($node->getChildNodes() as $childNode) {
            $this->walk($frame, $childNode);
        }
    }

    private function processMethodDeclaration(Frame $frame, MethodDeclaration $node)
    {
        $namespace = $node->getNamespaceDefinition();
        $class = $node->getFirstAncestor(ClassDeclaration::class);
        $frame->set('$this', $class);

        foreach ($node->parameters->children as $parameter) {
            if (false === $parameter instanceof Parameter) {
                continue;
            }

            $frame->set(
                $parameter->variableName->getText($node->getFileContents()),
                $parameter
            );
        }
    }
}
