<?php


namespace MkyCommand;


class ConsoleTable
{
    const HEADER_INDEX = -1;
    const HR = 'HR';

    protected array $data = [];

    protected bool $border = true;

    protected bool $allBorders = false;

    protected int $padding = 1;

    protected int $indent = 0;

    private int $rowIndex = -1;

    private array $columnWidths = [];

    private int $maxColumnCount = 0;

    /**
     * Adds a column to the table header
     * @param string $content Header cell content
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function addHeader(string $content = ''): ConsoleTable
    {
        $this->data[self::HEADER_INDEX][] = $content;

        return $this;
    }

    /**
     * Set headers for the columns in one-line
     * @param array $content Array of header cell content
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function setHeaders(array $content): ConsoleTable
    {
        $this->data[self::HEADER_INDEX] = $content;

        return $this;
    }

    /**
     * Get the row of header
     */
    public function getHeaders()
    {
        return $this->data[self::HEADER_INDEX] ?? null;
    }

    /**
     * Adds a row to the table
     * @param array|null $data The row data to add
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function addRow(array $data = null): ConsoleTable
    {
        $this->rowIndex++;

        if (is_array($data)) {
            foreach ($data as $col => $content) {
                $this->data[$this->rowIndex][$col] = $content;
            }

            $this->setMaxColumnCount(count($this->data[$this->rowIndex]));
        }

        return $this;
    }

    /**
     * Set max column count
     * @param int $count The column count
     */
    private function setMaxColumnCount(int $count): void
    {
        if ($count > $this->maxColumnCount) {
            $this->maxColumnCount = $count;
        }
    }

    /**
     * Adds a column to the table
     * @param mixed $content The data of the column
     * @param int|null $col The column index to populate
     * @param int|null $row If starting row is not zero, specify it here
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function addColumn(mixed $content, int $col = null, int $row = null): ConsoleTable
    {
        $row = $row === null ? $this->rowIndex : $row;
        if ($col === null) {
            $col = isset($this->data[$row]) ? count($this->data[$row]) : 0;
        }

        $this->data[$row][$col] = $content;
        $this->setMaxColumnCount(count($this->data[$row]));

        return $this;
    }

    /**
     * Hide table border
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function hideBorder(): ConsoleTable
    {
        $this->border = false;

        return $this;
    }

    /**
     * Show all table borders
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function showAllBorders(): ConsoleTable
    {
        $this->showBorder();
        $this->allBorders = true;

        return $this;
    }

    /**
     * Show table border
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function showBorder(): ConsoleTable
    {
        $this->border = true;

        return $this;
    }

    /**
     * Set padding for each cell
     * @param integer $value The integer value, defaults to 1
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function setPadding(int $value = 1): ConsoleTable
    {
        $this->padding = $value;

        return $this;
    }

    /**
     * Set left indentation for the table
     * @param integer $value The integer value, defaults to 1
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function setIndent(int $value = 0): ConsoleTable
    {
        $this->indent = $value;

        return $this;
    }

    /**
     * Add horizontal border line
     * @return ConsoleTable LucidFrame\Console\ConsoleTable
     */
    public function addBorderLine(): ConsoleTable
    {
        $this->rowIndex++;
        $this->data[$this->rowIndex] = self::HR;

        return $this;
    }

    /**
     * Print the table
     * @return void
     */
    public function display(): void
    {
        echo $this->getTable();
    }

    /**
     * Get the printable table content
     * @return string
     */
    public function getTable(): string
    {
        $this->calculateColumnWidth();

        $output = $this->border ? $this->getBorderLine() : '';
        foreach ($this->data as $y => $row) {
            if ($row === self::HR) {
                if (!$this->allBorders) {
                    $output .= $this->getBorderLine();
                    unset($this->data[$y]);
                }

                continue;
            }

            if ($y === self::HEADER_INDEX && count($row) < $this->maxColumnCount) {
                $row = $row + array_fill(count($row), $this->maxColumnCount - count($row), ' ');
            }

            foreach ($row as $x => $cell) {
                $output .= $this->getCellOutput($x, $row);
            }
            $output .= PHP_EOL;

            if ($y === self::HEADER_INDEX) {
                $output .= $this->getBorderLine();
            } else {
                if ($this->allBorders) {
                    $output .= $this->getBorderLine();
                }
            }
        }

        if (!$this->allBorders) {
            $output .= $this->border ? $this->getBorderLine() : '';
        }

        if (PHP_SAPI !== 'cli') {
            $output = '<pre>' . $output . '</pre>';
        }

        return $output;
    }

    /**
     * Calculate maximum width of each column
     * @return void
     */
    private function calculateColumnWidth(): void
    {
        foreach ($this->data as $row) {
            if (is_array($row)) {
                foreach ($row as $x => $col) {
                    $content = preg_replace('#\x1b[[][^A-Za-z]*[A-Za-z]#', '', $col);
                    if (!isset($this->columnWidths[$x])) {
                        $this->columnWidths[$x] = mb_strlen($content, 'UTF-8');
                    } else {
                        if (mb_strlen($content, 'UTF-8') > $this->columnWidths[$x]) {
                            $this->columnWidths[$x] = mb_strlen($content, 'UTF-8');
                        }
                    }
                }
            }
        }

    }

    /**
     * Get the printable borderline
     * @return string
     */
    private function getBorderLine(): string
    {
        $output = '';

        if (isset($this->data[0])) {
            $columnCount = count($this->data[0]);
        } elseif (isset($this->data[self::HEADER_INDEX])) {
            $columnCount = count($this->data[self::HEADER_INDEX]);
        } else {
            return $output;
        }

        for ($col = 0; $col < $columnCount; $col++) {
            $output .= $this->getCellOutput($col);
        }

        if ($this->border) {
            $output .= '+';
        }
        $output .= PHP_EOL;

        return $output;
    }

    /**
     * Get the printable cell content
     *
     * @param integer $index The column index
     * @param array|null $row The table row
     * @return string
     */
    private function getCellOutput(int $index, array $row = null): string
    {
        $cell = $row ? $row[$index] : '-';
        $width = $this->columnWidths[$index];
        $padding = str_repeat($row ? ' ' : '-', $this->padding);

        $output = '';

        if ($index === 0) {
            $output .= str_repeat(' ', $this->indent);
        }

        if ($this->border) {
            $output .= $row ? '|' : '+';
        }

        $output .= $padding; # left padding
        $cell = trim(preg_replace('/\s+/', ' ', $cell)); # remove line breaks
        $content = preg_replace('#\x1b[[][^A-Za-z]*[A-Za-z]#', '', $cell);
        $delta = mb_strlen($cell, 'UTF-8') - mb_strlen($content, 'UTF-8');
        $output .= $this->strPadUnicode($cell, $width + $delta, $row ? ' ' : '-'); # cell content
        $output .= $padding; # right padding
        if ($row && $index == count($row) - 1 && $this->border) {
            $output .= $row ? '|' : '+';
        }

        return $output;
    }

    /**
     * Multibyte version of str_pad() function
     * @source http://php.net/manual/en/function.str-pad.php
     */
    private function strPadUnicode($str, $padLength, $padString = ' ', int $dir = STR_PAD_RIGHT)
    {
        $strLen = mb_strlen($str, 'UTF-8');
        $padStrLen = mb_strlen($padString, 'UTF-8');

        if (!$strLen && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
            $strLen = 1;
        }

        if (!$padLength || !$padStrLen || $padLength <= $strLen) {
            return $str;
        }

        $result = null;
        $repeat = ceil($strLen - $padStrLen + $padLength);
        if ($dir == STR_PAD_RIGHT) {
            $result = $str . str_repeat($padString, $repeat);
            $result = mb_substr($result, 0, $padLength, 'UTF-8');
        } elseif ($dir == STR_PAD_LEFT) {
            $result = str_repeat($padString, $repeat) . $str;
            $result = mb_substr($result, -$padLength, null, 'UTF-8');
        } elseif ($dir == STR_PAD_BOTH) {
            $length = ($padLength - $strLen) / 2;
            $repeat = ceil($length / $padStrLen);
            $result = mb_substr(str_repeat($padString, $repeat), 0, floor($length), 'UTF-8')
                . $str
                . mb_substr(str_repeat($padString, $repeat), 0, ceil($length), 'UTF-8');
        }

        return $result;
    }
}