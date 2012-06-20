/**
 * Array Index Of
 *
 * This function adds the indexOf method to the Array prototype to fined the
 *   index of a value within an array.
 *
 * @param mixed The value to search for.
 * @param int The index of the value or -1 if not found.
 * @see http://stackoverflow.com/a/1181586/493474
 */
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function(needle) {
        for (var i = 0; i < this.length; i++) {
            if (this[i] === needle) {
                return i;
            }
        }
        return -1;
    };
}


/**
 * Wider Than
 *
 * This function determines whether the browser is wider than a break-point
 *   defined in the CSS.
 *
 * @param  string The name of the break-point.
 * @return bool
 * @see http://adactio.com/journal/5429/ (inspiration)
 */
window.wider_than = function(size) {
    var widerthan =
        window.getComputedStyle(document.body, ":after")
            .getPropertyValue("content");
    widerthan = widerthan.split(" ");
    return (widerthan.indexOf(size) !== -1);
};
