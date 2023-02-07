<?php

namespace MkyCore\Mail;

use MkyCore\Abstracts\AbstractMailerTemplate;

class MailerTemplate extends AbstractMailerTemplate
{
    protected string $viewPath = __DIR__ . '/views';
    protected bool $hasSignature = false;
    protected bool $hasFooter = false;
    protected bool $hasGreeting = false;

    public function paragraphs(array $paragraphs): MailerTemplate
    {
        foreach ($paragraphs as $paragraph) {
            $this->paragraph($paragraph);
        }
        return $this;
    }

    public function paragraph(string $paragraph): MailerTemplate
    {
        $this->blocks['contents'][] = "<p>$paragraph</p>";
        $this->texts['contents'][] = "$paragraph\n";
        return $this;
    }

    public function button(array $link, string $color = 'blue'): MailerTemplate
    {
        $title = key($link);
        $url = $link[$title];
        $this->blocks['contents'][] = <<<HTML
<table class="body-action">
    <tr align="center">
        <td>
            <table>
                <tr>
                    <td>
                        <a href="$url" class="button button--$color"
                           target="_blank">$title</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
HTML;
        $this->texts['contents'][] = "$title ($url)\n";
        return $this;
    }

    public function specialButton(array $link, string $color = 'blue', string $title = null, string $content = null): MailerTemplate
    {
        $titleLink = key($link);
        $url = $link[$titleLink];
        $this->texts['contents'][] = "\n";
        if ($title) {
            $this->texts['contents'][] = "$title\n";
        }
        if ($content) {
            $this->texts['contents'][] = "$content\n";
        }
        $title = $title ? "<h1 class=\"f-fallback discount_heading\">$title</h1>" : '';
        $content = $content ? "<p class=\"f-fallback discount_body\">$content</p>" : '';
        $this->texts['contents'][] = "$titleLink ($url)\n";
        $this->blocks['contents'][] = <<<HTML
<table class="discount">
  <tr>
    <td align="center">
      $title
      $content
      <table width="100%">
        <tr>
          <td align="center">
            <a href="$url" class="f-fallback button button--$color" target="_blank">$titleLink</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
        return $this;
    }

    public function greeting(string $greeting): MailerTemplate
    {
        if (!$this->hasGreeting) {
            $this->blocks['greeting'] = "<h1>$greeting</h1>";
            $count = strlen($greeting);
            $starts = str_repeat('*', $count);
            $this->texts['greeting'] = "$starts\n$greeting\n$starts\n";
            $this->hasGreeting = true;
        }
        return $this;
    }

    public function attributes(array $attributes): MailerTemplate
    {
        $block = "<table class=\"attributes\">
                       <tr>
                            <td class=\"attributes_content\">
                                <table>";
        $texts = [];
        foreach ($attributes as $key => $attribute) {
            if (is_string($key)) {
                $attr = "<strong>$key:</strong> $attribute";
                $texts[] = "$key: $attribute";
            } else {
                $attr = $attribute;
                $texts[] = $attribute;
            }
            $block .= "<tr>
                            <td class=\"attributes_item\">
                                <span>
                                    $attr
                                </span>
                            </td>
                        </tr>";
        }
        $block .= "</table>
                            </td>
                        </tr>
                    </table>";
        $this->blocks['contents'][] = $block;
        $this->texts['contents'][] = "\n" . join("\n", $texts) . "\n";
        return $this;
    }

    public function quote(): static
    {
        $this->blocks['contents'][] = "<div class=\"attributes_content\">";
        return $this;
    }

    public function endQuote(): static
    {
        $this->blocks['contents'][] = "</div>";
        return $this;
    }

    public function footer(string $footer, array $link = null): MailerTemplate
    {
        if (!$this->hasFooter) {
            $text = '';
            $linkHTML = '';
            if ($link) {
                $title = key($link);
                $linkHTML = "<p class=\"sub\"><a href=\"$link[$title]\">$title</a></p>";
                $text = " $title ($link[$title])";
            }
            $this->blocks['footer'] = "<table class=\"body-sub\">
                                <tr>
                                    <td>
                                        <p class=\"sub\">$footer</p>$linkHTML
                                    </td>
                                </tr>
                            </table>";
            $this->texts['footer'] = "\n$footer$text\n";
            $this->hasFooter = true;
        }
        return $this;
    }

    public function signature(array|string $signatures): MailerTemplate
    {
        if (!$this->hasSignature) {
            $signaturesHTML = join("<br>", (array)$signatures);
            $this->blocks['signature'] = "<tr>
                    <td>
                        <table class=\"email-footer\">
                            <tr>
                                <td class=\"content-cell\">
                                    <p class=\"sub align-center\">
                                        $signaturesHTML
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>";
            $this->texts['signature'] = "\n" . join("\n", (array)$signatures) . "\n";
            $this->hasSignature = true;
        }
        return $this;
    }
}