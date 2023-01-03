<?php

namespace MkyCore\Mail;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\ViewSystemException;
use MkyCore\Facades\View;
use MkyCore\Interfaces\MailerTemplateInterface;
use ReflectionException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailerTemplate implements MailerTemplateInterface
{
    protected string $viewPath = __DIR__ . '/views';
    protected string $viewNamespace = 'mailer';
    protected string $viewTwig = 'template.twig';
    protected string $viewText = 'template.txt';
    protected array $texts = [];
    protected array $blocks = [];
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
        $this->texts['contents'][] = $paragraph;
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
        $this->texts['contents'][] = "$title ($url)";
        return $this;
    }

    public function specialButton(array $link, string $color = 'blue', string $title = null, string $content = null): MailerTemplate
    {
        $titleLink = key($link);
        $url = $link[$titleLink];
        $title = $title ? "<h1 class=\"f-fallback discount_heading\">$title</h1>" : '';
        $content = $content ? "<p class=\"f-fallback discount_body\">$content</p>" : '';
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
            $this->texts['greeting'] = "$starts\n$greeting\n$starts";
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
            $attr = $attribute;
            $texts[] = $attribute;
            if (is_string($key)) {
                $attr = "<strong>$key:</strong> $attribute";
                $texts[] = "$key: $attribute";
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
        $this->texts['contents'][] = join("\n", $texts);
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
            $this->texts['footer'] = "$footer$text";
            $this->hasFooter = true;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function use(array|string $viewTemplates): MailerTemplate
    {
        if (is_array($viewTemplates)) {
            $this->viewTwig = $viewTemplates['twig'] ?? '';
            $this->viewText = $viewTemplates['text'] ?? '';
        } else if (is_string($viewTemplates)) {
            $this->viewTwig = $viewTemplates;
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
            $this->texts['signature'] = join("\n\n", (array)$signatures);
            $this->hasSignature = true;
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @throws FailedToResolveContainerException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws ViewSystemException
     * @throws SyntaxError
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function generate(): string
    {
        list($namespace, $twigRender) = $this->outPutTemplate();
        return $twigRender->toHtml("@$namespace/" . $this->viewTwig, $this->blocks);
    }

    public function generateText(): string|false
    {
        list($namespace, $twigRender) = $this->outPutTemplate();
        return $twigRender->toHtml("@$namespace/" . $this->viewText, $this->texts);
    }

    /**
     * @return array
     */
    private function outPutTemplate(): array
    {
        $namespace = $this->viewNamespace;
        $twigRender = View::addPath($this->viewPath, $namespace);
        return array($namespace, $twigRender);
    }
}