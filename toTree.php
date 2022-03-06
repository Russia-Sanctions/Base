<?php

abstract class Node
{
    public $prefix;
    public $mask;

    public function isParentOf($node): bool
    {
        return ($node->prefix & $this->mask) === $this->prefix;
    }
}

class Branch extends Node
{
    public $left;
    public $right;

    public static function commonParent(Node $child1, Node $child2): self
    {
        $node = new self();
        $node->mask = 0x0;
        while (($child1->prefix & $node->mask) === ($child2->prefix & $node->mask)) {
            $node->mask = ($node->mask >> 1) | 0x80000000;
        }
        $node->mask = $node->mask << 1;
        $node->prefix = $child1->prefix & $node->mask;
        $node->left = $child1;
        $node->right = $child2;

        return $node;
    }
}

class Leaf extends Node
{
    public $type;

    public static function fromCidr(string $cidr, string $type): self
    {
        $leaf = new self();
        $leaf->type = $type;
        $parts = explode('/', $cidr);
        $leaf->prefix = ip2long($parts[0]);
        $shift = 32 - (int)$parts[1];
        $leaf->mask = (0xffffffff >> $shift) << $shift;

        return $leaf;
    }
}

$tree = null;

function add_country(string $code) {
    global $tree;

    $handle = fopen("ips/ipv4/" . $code, "r");
    if (!$handle) {
        // err
    }

    while (($line = fgets($handle)) !== false) {
        if ($line{0} == '#') {
            continue;
        }

        $leaf = Leaf::fromCidr($line, strtoupper($code));

        if (!$tree) {
            $tree = $leaf;
            continue;
        }
        if (!$tree->isParentOf($leaf)) {
            $tree = Branch::commonParent($tree, $leaf);
            continue;
        }

        $node = $tree;
        while (1) {
            if (is_a($node, Leaf::class)) {
                break;
            } elseif ($node->left->isParentOf($leaf)) {
                $node = $node->left;
            } elseif ($node->right->isParentOf($leaf)) {
                $node = $node->right;
            } else {
                break;
            }
        }

        $newLeft = Branch::commonParent($node->left, $leaf);
        $newRight = Branch::commonParent($node->right, $leaf);

        if ($newLeft->prefix > $newRight->prefix) {
            $node->left = $newLeft;
        } else {
            $node->right = $newRight;
        }
    }

    fclose($handle);
}

function ptree(Node $node): string {
    $str = pack("N", $node->prefix) . pack("N", $node->mask);

    if (is_a($node, Leaf::class)) {
        return $str . "\xff\xff" . $node->type;
    }

    $data = ptree($node->left) . ptree($node->right);
    return $str . pack("N", strlen($data)) . $data;
}

add_country('ru');
add_country('by');

file_put_contents("trees/ipv4.bin", ptree($tree));
