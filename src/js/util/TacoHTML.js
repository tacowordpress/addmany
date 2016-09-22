import TacoObj from './TacoObj.js';
import TacoStr from './TacoStr.js';
import TacoGeneral from './TacoGeneral.js';
import TacoArr from './TacoArr.js';

export default class TacoHTML {

  render(str, html) {
    html = (typeof html != 'undefined')
      ? html
      : false;
    return (html === true)
      ? str
      : TacoStr.htmlEntities(str);
  };

  attribs(attribs, leading_space) {

    leading_space = (typeof leading_space != 'undefined')
      ? leading_space
      : true;
    if(TacoObj.getObjectLength(attribs) < 1) return '';

    var out = [];
    for(var key in attribs) {

      var value = attribs[key];
      value = (typeof value == 'object') ? TacoObj.objectJoin(' ', value) : value;
      out.push(key + '="' + String(value).replace(/\"/, '\"') + '"');
    }
    return ((leading_space) ? ' ' : '') + out.join(' ');
  };

  getTextInputTypes() {
    return [
      'text',
      'image',
      'file',
      'search',
      'email',
      'url',
      'tel',
      'number',
      'range',
      'date',
      'month',
      'week',
      'time',
      'datetime',
      'datetime-local',
      'color'
    ];
  };

  tag(element_type, body, attribs, close, is_html) {
    body = (typeof body == 'undefined' || body === null)
      ? ''
      : body;
    attribs = (typeof attribs == 'undefined')
      ? []
      : attribs;
    close = (typeof close == 'undefined')
      ? true
      : close;
    is_html = (typeof is_html == 'undefined')
      ? false
      : is_html;

    var not_self_closing = ['a', 'div', 'iframe', 'textarea'];
    var is_self_closing = false;
    if(close && TacoGeneral.empty(body) && !TacoArr.inArray(
      element_type.toLowerCase(),
      not_self_closing
    )) {
      is_self_closing = true;
    }

    if(is_self_closing) {
      return '<' + element_type + this.attribs(attribs) + ' />';
    }
    return [
      '<' + element_type + this.attribs(attribs) + '>',
      this.render(body, is_html),
      (close) ? '</' + element_type + '>' : ''
    ].join('');
  };

  selecty(options, selected, attribs) {
    selected = (typeof selected != 'undefined')
      ? selected
      : null;
    attribs = (typeof attribs != 'undefined')
      ? attribs
      : [];
    var htmls = [];
    htmls.push(this.tag('select', null, attribs, false));

    if(TacoObj.isIterable(options)) {
      for(var key in options) {
        value = options[key];
        var option_attribs = { value: key };
        if(String(selected) === String(value)) {
          option_attribs.selected = 'selected';
        }
        htmls.push(this.tag('option', value, option_attribs));
      }
    }
    htmls.push('</select>');
    return htmls.join("\n");
  };

}
