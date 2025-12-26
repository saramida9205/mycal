/**
 * Korean Lunar Calendar Converter (Safe Version)
 */
const LunarCal = (function () {
    // Simplified Lunar Data for core years (approximate for valid rendering test)
    // Real implementation requires accurate data table.
    // Preserving the array structure but ensuring safety.
    const lunarData = [
        0x10d2, 0x0d95, 0x05b5, 0x056a, 0x155b, 0x025d, 0x092d, 0x0d2b, 0x0a95, 0x0b55,
        0x16aa, 0x0ad5, 0x05b5, 0x04b6, 0x1a5d, 0x0a4d, 0x0d25, 0x1d52, 0x0b54, 0x0b6a,
        0x155a, 0x056d, 0x095b, 0x049b, 0x1a4b, 0x0a4b, 0x0aa5, 0x1b55, 0x06d2, 0x0ada,
        0x1ab5, 0x095d, 0x049d, 0x1a9d, 0x0a9d, 0x0b55, 0x16d5, 0x0ad5, 0x055a, 0x04ba,
        0x1a5b, 0x052b, 0x052b, 0x1a95, 0x0b95, 0x06aa, 0x1ad5, 0x05b5, 0x04b6, 0x1a57,
        0x0a4d, 0x0d26, 0x1d96, 0x0d55, 0x056a, 0x155a, 0x025d, 0x092d, 0x192b, 0x0a95,
        0x0b95, 0x16ca, 0x0ad5, 0x05b5, 0x04ba, 0x1a5b, 0x052b, 0x052b, 0x1a93, 0x0a95,
        0x06aa, 0x1ad5, 0x05b5, 0x04b6, 0x1a57, 0x0a4d, 0x0a26, 0x1d16, 0x0d55, 0x04aa,
        0x155a, 0x095d, 0x092d, 0x192b, 0x0a95, 0x0b95, 0x16aa, 0x0ad5, 0x05b5, 0x04ba,
        0x1a5b, 0x052b, 0x052b, 0x1a95, 0x0a95, 0x06aa, 0x1ad5, 0x05b5, 0x04b6, 0x1657,
        0x0a4d, 0x0d26, 0x1d52, 0x0d55, 0x056a, 0x155a, 0x055d, 0x092b, 0x192b, 0x0a95,
        0x0b95, 0x16aa, 0x0ad5, 0x05b5, 0x04b6, 0x1a5b, 0x0a57, 0x052b, 0x1a93, 0x0a95,
        0x06aa, 0x1ad5, 0x05b5, 0x04b6, 0x1657, 0x0a4d, 0x0d26, 0x1d52, 0x0d55, 0x056a,
        0x155a, 0x055d, 0x092b, 0x192b, 0x0a95, 0x0b95, 0x16aa, 0x0ad5, 0x05b5, 0x04b6,
        0x1a5b, 0x0a2b, 0x052b, 0x1a93, 0x0a95, 0x06aa, 0x1ad5, 0x05b5, 0x04b6, 0x1657,
        0x0a4d
    ];

    function solarToLunar(year, month, day) {
        if (year < 1900 || year > 2050) return null;

        // Base date: 1900-01-31 Solar = 1900-01-01 Lunar
        var baseDate = new Date(1900, 0, 31);
        var objDate = new Date(year, month - 1, day);
        var offset = Math.floor((objDate - baseDate) / 86400000);

        var iYear, daysOfYear = 0;
        for (iYear = 1900; iYear < 2050 && offset > 0; iYear++) {
            daysOfYear = getLunarYearDays(iYear);
            if (offset < daysOfYear) break;
            offset -= daysOfYear;
        }

        if (iYear >= 2050 && offset >= daysOfYear) {
            return null; // Out of bounds
        }

        var lunYear = iYear;
        var leapMonth = lunarData[iYear - 1900] & 0xf;
        var iMonth, daysOfMonth = 0;
        var isLeap = false;

        for (iMonth = 1; iMonth < 13; iMonth++) {
            // Normal month
            daysOfMonth = getDays(lunYear, iMonth, false);
            if (offset < daysOfMonth) break;
            offset -= daysOfMonth;

            // Leap month check
            if (leapMonth === iMonth) {
                daysOfMonth = getDays(lunYear, iMonth, true);
                if (offset < daysOfMonth) {
                    isLeap = true;
                    break;
                }
                offset -= daysOfMonth;
            }
        }

        return {
            year: lunYear,
            month: iMonth,
            day: offset + 1,
            isLeap: isLeap
        };
    }

    function getLunarYearDays(year) {
        var days = 0;
        // 12 months
        for (var i = 1; i <= 12; i++) {
            days += getDays(year, i, false);
        }
        // Leap month
        var leap = lunarData[year - 1900] & 0xf;
        if (leap > 0 && leap <= 12) {
            days += getDays(year, leap, true);
        }
        return days;
    }

    function getDays(year, month, isLeap) {
        if (year < 1900 || year > 2050) return 29;
        var idx = year - 1900;
        var data = lunarData[idx];

        if (isLeap) {
            // For safety and simplicity, we assume 29 except if high bit is set (if data supports it).
            if ((lunarData[idx] & 0x10000)) return 30;
            return 29;
        } else {
            return (lunarData[idx] & (1 << (16 - month))) ? 30 : 29;
        }
    }

    return {
        solarToLunar: solarToLunar
    };
})();
