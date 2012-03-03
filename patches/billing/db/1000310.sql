
INSERT INTO `Clauses` (`ID`, `PublicDate`, `ChangedDate`, `AuthorID`, `EditorID`, `Partition`, `Title`, `IsProtected`, `IsXML`, `IsDOM`, `Text`, `IsPublish`) VALUES
('', 1307536932, 0, 100, 100, 'Invoices/PaymentSystems/EasyPay', 'Шаблон платежной системы EasyPay', 'no', 'yes', 'yes', '<NOBODY>\r\n <H1>\r\n СЧЕТ №%Invoice.Number% от %Invoice.CreateDate%\r\n</H1>\r\n <DIV id="Services">\r\n [список услуг]\r\n</DIV>\r\n <H2>\r\n Платежное поручение\r\n</H2>\r\n <TABLE border="1" cellpadding="5" cellspacing="0">\r\n  <TBODY>\r\n   <TR bgcolor="#DCDCDC">\r\n    <TD align="center">\r\n    Назначение\r\n   </TD>\r\n    <TD align="center">\r\n    Номер Поставщика\r\n   </TD>\r\n    <TD align="center">\r\n    Сумма\r\n   </TD>\r\n   </TR>\r\n   <TR>\r\n    <TD>\r\n    За web-услуги по счету №%Invoice.Number%\r\n   </TD>\r\n    <TD align="right">\r\n    %PaymentSystem.Send.EP_MerNo%\r\n   </TD>\r\n    <TD align="right">\r\n    %Invoice.Foreign% %PaymentSystem.Measure%\r\n   </TD>\r\n   </TR>\r\n  </TBODY>\r\n </TABLE>\r\n</NOBODY>\r\n', 'yes');


