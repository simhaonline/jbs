{*
 *  Joonte Billing System
 *  Copyright © 2020 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Оканчивается срок действия заказа на домен {$DomainName|default:'$DomainName'}.{$DomainZone|default:'$DomainZone'}" scope=global}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Уведомляем Вас о том, что оканчивается срок регистрации доменного имени {$DomainName|default:'$DomainName'}.{$DomainZone|default:'$DomainZone'}. Номер заказа #{$OrderID|string_format:"%05u"}.
Пожалуйста, не забудьте своевременно продлить Ваш заказ, иначе он будет заблокирован и аннулирован, а Ваше доменное имя смогут занять другие люди.
Дата окончания заказа:	{$ExpirationDate|date_format:"%d.%m.%Y"}.
Баланс договора:	{$Balance|default:'$Balance'}
Стоимость продления:	{$Cost|default:'$Cost'}

--
Обращаем Ваше внимание, что последнее время участились факты фишинговых рассылок с предложением продлить домен, иначе он будет удалён/продан/заблокирован - на что хватает фантазии у создателей рассыки.
Также могут предлагать "регистрацию в поисковых системах", проверку, подтверждение владением и т.п.
В письме содерджится ссылка на оплату, но домен они, в реальности, не продлевают - просто обманывают. Будьте внимательны, проверяйте сайт, на который ведёт ссылка на оплату.

Единственный вариант, когда может быть письмо не от нас - это международные домены, в некоторых зонах требуют подтвердить контактный адрес владельца домена. Бесплатно.

{if !$MethodSettings.CutSign}
--
{$From.Sign|default:'$From.Sign'}

{/if}

