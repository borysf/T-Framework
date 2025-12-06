<section class="synopsis">
    <h3><com:Literal id="Title" /></h3>
    <div class="code">
        <com:Repeater id="List" onItemDataBind="List_DataBind">
            <prop:headerTemplate>
                <ul>
            </prop:headerTemplate>
            <prop:itemTemplate>
                    <li>
                        <com:.classes.AttributesList id="Attributes" />
                        <com:Text id="Modifiers" html.class="modifiers" />
                        <com:.classes.TypesList id="Type" /> 
                        $<com:HyperLink id="Name" html.class="property name" />
                        <com:Container id="ValueContainer" visible="false">
                            = <com:Text id="Value" html.class="value" />
                        </com:Container>
                        <com:Text id="Prop" html.class="badge prop" visible="false">Prop</com:Text>
                        <com:Text id="Stateful" html.class="badge stateful" visible="false">Stateful</com:Text>

                        <com:Text id="InheritedFrom" visible="false" html.class="badge inherited"  html.title="Inherited">
                            <com:HyperLink id="InheritedFromLink" />
                        </com:Text>
                        <com:.classes.Comment id="Comment" />
                    </li>
            </prop:itemTemplate>
            <prop:footerTemplate>
                </ul>
            </prop:footerTemplate>
        </com:Repeater>
    </div>
</section>